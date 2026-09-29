import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { FaceQueue, faceKey } from '../faces';
import { CYAN, GREEN, MAGENTA, RED, YELLOW, boom, explode, neonStyle, toInt } from '../neon';
import Bullet from '../objects/Bullet';
import Enemy from '../objects/Enemy';
import Player from '../objects/Player';
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
        this.syncHud();
        this.syncQueue();

        if (!this.scene.isActive('hud')) {
            this.scene.launch('hud');
        }

        this.bullets = this.physics.add.group({ classType: Bullet, maxSize: 60, runChildUpdate: true });
        this.enemies = this.physics.add.group({ classType: Enemy, maxSize: 100, runChildUpdate: true });

        this.player = new Player(this, WIDTH / 2, HEIGHT / 2, {
            fireRate: balance.fire_rate,
            turnSpeed: balance.turn_speed,
            bullets: this.bullets,
        });

        this.physics.add.overlap(this.bullets, this.enemies, (bullet, enemy) => {
            if (!bullet.active || !enemy.active) {
                return;
            }

            bullet.kill();

            if (enemy.hit(balance.bullet_damage)) {
                explode(this, enemy.x, enemy.y, enemy.colour, enemy.scale);
                this.cameras.main.shake(90, 0.002);
                this.state.recordKill();
                this.syncHud();
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

        this.waves.start();
    }

    update(time, delta) {
        if (this.state.dead) {
            return;
        }

        const enemies = this.enemies.getMatching('active', true);

        this.player.update(time, delta, enemies);
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
        applyUpgrade(upgrade, { player: this.player, state: this.state, balance: this.balance });
        this.syncHud();
        this.banner(`${upgrade.name.toUpperCase()}\n${upgrade.describe(this.balance)}`, { colour: YELLOW, size: 90, holdMs: 700 });
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
