import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { FaceQueue } from '../faces';
import Bullet from '../objects/Bullet';
import Enemy from '../objects/Enemy';
import Player from '../objects/Player';
import { RunState } from '../state';
import { Waves } from '../waves';

// Pause between waves until the chat vote replaces it (#17, #18).
const BREAK_BETWEEN_WAVES_MS = 3000;

// How long "Game over" shows before a new run starts.
const GAME_OVER_MS = 5000;

export default class GameScene extends Phaser.Scene {
    constructor() {
        super('game');
    }

    create() {
        const { balance, faces } = this.registry.get('run');
        this.faces = new FaceQueue(faces);
        this.state = new RunState({ startingHealth: balance.starting_health });
        this.syncHud();

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
                this.explode(enemy.x, enemy.y);
                this.state.recordKill();
                this.syncHud();
            }
        });

        // Scene events outlive a restart, so unsubscribe or every new run would take double damage.
        this.events.on('enemy-reached-base', this.damagePlayer, this);
        this.events.once('shutdown', () => this.events.off('enemy-reached-base', this.damagePlayer, this));

        this.waves = new Waves(balance, {
            spawn: ({ health, speed }) => {
                const enemy = this.enemies.get();
                enemy?.spawn({ face: this.faces.next(), health, speed });

                return Boolean(enemy);
            },
            onStart: (wave) => {
                this.state.startWave(wave);
                this.syncHud();
                this.banner(`Wave ${wave}`);
            },
            onCleared: (wave) => {
                if (this.state.dead) {
                    return;
                }

                this.state.waveCleared();
                this.syncHud();
                this.banner(`Wave ${wave} cleared!`);
                this.time.delayedCall(BREAK_BETWEEN_WAVES_MS, () => this.waves.next());
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

    damagePlayer() {
        if (this.state.dead) {
            return;
        }

        const fatal = this.state.takeDamage(1);
        this.syncHud();
        this.player.flash();
        this.cameras.main.shake(200, 0.006);

        if (fatal) {
            this.gameOver();
        }
    }

    /**
     * The run is over: show how it went, keep the stats, then start a fresh
     * run from wave 1 via the boot scene so balance and faces are reloaded.
     */
    gameOver() {
        this.state.end();
        // Kept for saving at the end of the run (#28).
        this.registry.set('lastRunStats', this.state.stats());

        this.enemies.getMatching('active', true).forEach((enemy) => {
            this.explode(enemy.x, enemy.y);
            enemy.kill();
        });
        this.explode(this.player.x, this.player.y, 4);
        this.player.ship.setVisible(false);

        this.banner(`Game over\nWave ${this.state.wave} · ${this.state.score.toLocaleString()} pts`, GAME_OVER_MS - 600);
        this.time.delayedCall(GAME_OVER_MS, () => this.scene.start('boot'));
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

    explode(x, y, size = 1) {
        const flash = this.add.image(x, y, 'hit').setDepth(20).setScale(0.6 * size);

        this.tweens.add({
            targets: flash,
            scale: 2 * size,
            alpha: 0,
            angle: 90,
            duration: 250,
            onComplete: () => flash.destroy(),
        });
    }

    /**
     * A big message in the middle of the screen that fades away.
     */
    banner(message, holdMs = 1200) {
        const text = this.add
            .text(WIDTH / 2, HEIGHT / 2 - 200, message, {
                fontFamily: 'system-ui, sans-serif',
                fontSize: '80px',
                fontStyle: 'bold',
                color: '#ffffff',
                stroke: '#000000',
                strokeThickness: 10,
                align: 'center',
            })
            .setOrigin(0.5)
            .setDepth(30)
            .setAlpha(0);

        this.tweens.chain({
            targets: text,
            tweens: [
                { alpha: 1, scale: { from: 0.8, to: 1 }, duration: 250, ease: 'Back.out' },
                { alpha: 0, delay: holdMs, duration: 400 },
            ],
            onComplete: () => text.destroy(),
        });
    }
}
