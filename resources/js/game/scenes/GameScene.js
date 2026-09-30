import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { Arsenal, blastRadius, chainTargets, within } from '../arsenal';
import { FaceQueue, faceKey } from '../faces';
import { CYAN, GREEN, MAGENTA, ORANGE, RED, YELLOW, boom, explode, neonStyle, toInt } from '../neon';
import Bullet from '../objects/Bullet';
import Enemy from '../objects/Enemy';
import Player from '../objects/Player';
import Blades from '../powers/Blades';
import Drones from '../powers/Drones';
import Nova from '../powers/Nova';
import { RunState } from '../state';
import { applyUpgrade, upgrades } from '../upgrades';
import { Waves } from '../waves';

// Moments between a wave clearing, the vote, and the next wave.
const BEFORE_VOTE_MS = 1500;
const AFTER_UPGRADE_MS = 2000;

// How long "Game over" shows before a new run starts.
const GAME_OVER_MS = 7000;

// How many followers the HUD shows as coming next.
const QUEUE_LENGTH = 6;

export default class GameScene extends Phaser.Scene {
    constructor() {
        super('game');
    }

    create() {
        const { balance, faces, streamer } = this.registry.get('run');
        this.balance = balance;
        // ?vote-seconds only shortens local votes; chat votes last as long as Laravel's window.
        const localSeconds = this.registry.get('localVote') && this.registry.get('voteSeconds');
        this.voteSeconds = localSeconds || streamer.voting_window_seconds;
        this.faces = new FaceQueue(faces);
        this.state = new RunState({ startingHealth: balance.starting_health });
        this.arsenal = new Arsenal();
        this.syncHud();
        this.syncQueue();

        if (!this.scene.isActive('hud')) {
            this.scene.launch('hud');
        }

        // Plenty of bolts, for multishot fans that ricochet around the screen.
        this.bullets = this.physics.add.group({ classType: Bullet, maxSize: 400, runChildUpdate: true });
        this.enemies = this.physics.add.group({ classType: Enemy, maxSize: 100, runChildUpdate: true });

        this.player = new Player(this, WIDTH / 2, HEIGHT / 2, {
            fireRate: balance.fire_rate,
            turnSpeed: balance.turn_speed,
            bullets: this.bullets,
            arsenal: this.arsenal,
        });
        this.powers = [new Blades(this, this.player, this.arsenal), new Drones(this, this.player, this.arsenal, this.bullets), new Nova(this, this.player, this.arsenal)];

        this.physics.add.overlap(this.bullets, this.enemies, (bullet, enemy) => {
            if (bullet.active && enemy.active && bullet.strike(enemy)) {
                this.hitEnemy(enemy, balance.bullet_damage);
            }
        });

        // Scene events outlive a restart, so unsubscribe or every new run would take double damage.
        this.events.on('enemy-reached-base', this.damagePlayer, this);
        this.events.once('shutdown', () => this.events.off('enemy-reached-base', this.damagePlayer, this));

        // A test message from `php artisan game:ping`.
        const ping = ({ message }) => this.banner(message, { colour: CYAN, size: 64 });
        this.game.events.on('ping', ping);
        this.events.once('shutdown', () => this.game.events.off('ping', ping));

        this.waves = new Waves(balance, {
            spawn: ({ health, speed }) => {
                const enemy = this.enemies.get();
                enemy?.spawn({ face: this.faces.next(), health, speed });
                this.syncQueue();

                return Boolean(enemy);
            },
            onStart: (wave) => {
                this.state.startWave(wave);
                this.syncHud();
                this.banner(`WAVE ${wave}`, { colour: CYAN });
            },
            onCleared: (wave) => {
                if (this.state.dead) {
                    return;
                }

                this.state.waveCleared();
                this.syncHud();
                this.banner(`WAVE ${wave}\nCLEARED!`, { colour: GREEN, holdMs: 500 });
                this.time.delayedCall(BEFORE_VOTE_MS, () => this.startVote());
            },
        });

        // Dev only: ?give= starts the run with upgrades already stacked.
        this.registry.get('give').forEach((id) => {
            const upgrade = upgrades.get(id);

            if (upgrade) {
                applyUpgrade(upgrade, { player: this.player, state: this.state, arsenal: this.arsenal, balance: this.balance });
            } else {
                console.warn(`[face-invaders] ?give: no upgrade called "${id}"`);
            }
        });
        this.syncHud();

        this.waves.start();
    }

    update(time, delta) {
        if (this.state.dead) {
            return;
        }

        const enemies = this.enemies.getMatching('active', true);

        this.player.update(time, delta, enemies);
        this.powers.forEach((power) => power.update(time, delta, enemies));
        this.waves.update(delta, enemies.length);
    }

    /**
     * Between waves: vote on one of a few upgrades, apply it, then start the next wave.
     */
    startVote() {
        this.scene.launch('vote', {
            options: upgrades.draw(this.balance.upgrade_options_per_vote),
            balance: this.balance,
            wave: this.state.wave,
            seconds: this.voteSeconds,
            onDecided: (upgrade) => {
                this.applyUpgrade(upgrade);
                this.time.delayedCall(AFTER_UPGRADE_MS, () => this.waves.next());
            },
        });
    }

    applyUpgrade(upgrade) {
        applyUpgrade(upgrade, { player: this.player, state: this.state, arsenal: this.arsenal, balance: this.balance });
        this.syncHud();
        this.banner(`${upgrade.name.toUpperCase()}\n${upgrade.describe(this.balance)}`, { colour: YELLOW, size: 90, holdMs: 700 });
    }

    /**
     * Every way of hurting a rock goes through here, so any hit can arc
     * chain lightning and any kill can set off an explosion.
     *
     * @param {{arc?: boolean}} options arc: false for hits that are themselves lightning
     */
    hitEnemy(enemy, damage, { arc = true } = {}) {
        if (!enemy.active || this.state.dead) {
            return;
        }

        if (arc && this.arsenal.chain > 0) {
            this.lightning(enemy, damage);
        }

        if (enemy.hit(damage)) {
            this.killed(enemy);
        }
    }

    killed(enemy) {
        explode(this, enemy.x, enemy.y, enemy.colour, enemy.scale);
        this.cameras.main.shake(90, 0.002);
        this.state.recordKill();
        this.syncHud();

        if (this.arsenal.explosive > 0) {
            this.blast(enemy.x, enemy.y);
        }
    }

    /**
     * Explosive rounds: a kill bursts and damages nearby rocks. Each blast
     * lands a beat later, so chain reactions ripple across the screen.
     */
    blast(x, y) {
        const radius = blastRadius(this.arsenal.explosive);

        explode(this, x, y, ORANGE, radius / 60);
        this.time.delayedCall(90, () => {
            within({ x, y }, this.enemies.getMatching('active', true), radius).forEach((enemy) => this.hitEnemy(enemy, this.balance.bullet_damage, { arc: false }));
        });
    }

    /**
     * Chain lightning: a jagged bolt jumps from the struck rock to the next
     * nearest ones, hurting each.
     */
    lightning(from, damage) {
        const targets = chainTargets(from, this.enemies.getMatching('active', true), this.arsenal.chain);

        if (targets.length === 0) {
            return;
        }

        const bolt = this.add.graphics().setDepth(18);
        let last = from;

        targets.forEach((target) => {
            [[22, 0.18], [10, 0.55], [4, 1]].forEach(([width, alpha]) => {
                bolt.lineStyle(width, alpha === 1 ? 0xffffff : toInt(CYAN), alpha);
                bolt.strokePoints(jagged(last, target), false);
            });
            last = target;
        });

        this.tweens.add({ targets: bolt, alpha: 0, duration: 220, onComplete: () => bolt.destroy() });
        targets.forEach((target) => this.hitEnemy(target, damage, { arc: false }));
    }

    damagePlayer(enemy) {
        if (this.state.dead) {
            return;
        }

        const fatal = this.state.takeDamage(1);
        this.syncHud();
        this.player.flash();
        explode(this, enemy.x, enemy.y, enemy.colour, 1.5);
        this.cameras.main.shake(250, 0.01);

        if (fatal) {
            this.gameOver(enemy);
        }
    }

    /**
     * The run is over: show how it went, keep the stats, then start a fresh
     * run from wave 1 via the boot scene so balance and faces are reloaded.
     */
    gameOver(killer) {
        this.state.end();
        // Kept for saving at the end of the run (#28).
        this.registry.set('lastRunStats', this.state.stats());

        this.enemies.getMatching('active', true).forEach((enemy) => {
            explode(this, enemy.x, enemy.y, enemy.colour, enemy.scale);
            enemy.kill();
        });
        explode(this, this.player.x, this.player.y, CYAN, 4);
        this.player.ship.setVisible(false);
        this.player.flame.setVisible(false);
        this.powers.forEach((power) => power.hide?.());
        this.cameras.main.shake(600, 0.02);

        this.banner('GAME OVER', { colour: RED, size: 150, holdMs: GAME_OVER_MS - 2500, y: 230 });
        // The killer's asteroid is back in the pool, so keep what we need of it now.
        const { face, colour } = killer;
        this.time.delayedCall(700, () => this.showKiller({ face, colour }));
        this.time.delayedCall(GAME_OVER_MS, () => this.scene.start('boot'));
    }

    /**
     * Name and shame the follower whose asteroid landed the final blow.
     */
    showKiller({ face, colour }) {
        const ring = this.add.graphics();

        [[36, 0.06], [20, 0.15], [10, 0.4], [5, 1]].forEach(([width, alpha]) => {
            ring.lineStyle(width, toInt(colour), alpha).strokeCircle(0, 0, 118);
        });

        const panel = this.add.container(WIDTH / 2, HEIGHT / 2 + 90, [
            this.add.text(0, -200, 'KILLED BY', neonStyle(MAGENTA, 44)).setOrigin(0.5),
            ring,
            this.add.text(0, 190, face?.name ?? 'A MYSTERY ROCK', neonStyle(colour, 72)).setOrigin(0.5),
            this.add.text(0, 285, `WAVE ${this.state.wave}  ·  ${this.state.score.toLocaleString()} PTS`, neonStyle(CYAN, 36)).setOrigin(0.5),
        ]).setDepth(31);

        if (face && this.textures.exists(faceKey(face))) {
            panel.add(this.add.image(0, 0, faceKey(face)).setDisplaySize(220, 220));
        }

        explode(this, panel.x, panel.y, colour, 3, 30);
        this.tweens.add({ targets: panel, alpha: { from: 0, to: 1 }, scale: { from: 0.3, to: 1 }, duration: 500, ease: 'Back.out' });
        this.tweens.add({ targets: ring, angle: 360, duration: 6000, repeat: -1 });
    }

    /**
     * Publish who's coming next for the HUD.
     */
    syncQueue() {
        this.registry.set('queue', this.faces.peek(QUEUE_LENGTH));
    }

    /**
     * Publish the run's numbers for the HUD.
     */
    syncHud() {
        this.registry.set({
            health: this.state.health,
            maxHealth: this.state.maxHealth,
            wave: Math.max(1, this.state.wave),
            score: this.state.score,
        });
    }

    /**
     * A big exploding neon message in the middle of the screen.
     */
    banner(message, options = {}) {
        boom(this, message, options);
    }
}

/**
 * A zigzag between two points, for lightning.
 */
function jagged(from, to, segments = 7) {
    const points = [new Phaser.Math.Vector2(from.x, from.y)];

    for (let i = 1; i < segments; i++) {
        const t = i / segments;
        points.push(
            new Phaser.Math.Vector2(
                from.x + (to.x - from.x) * t + Phaser.Math.Between(-18, 18),
                from.y + (to.y - from.y) * t + Phaser.Math.Between(-18, 18),
            ),
        );
    }

    points.push(new Phaser.Math.Vector2(to.x, to.y));

    return points;
}
