import Phaser from 'phaser';
import { TUNING, novaInterval, novaRadius, within } from '../arsenal';
import { YELLOW, toInt } from '../neon';

const EXPAND_MS = 450;

/**
 * Nova pulse: every few seconds a shockwave ring blasts out from the ship,
 * damaging every rock it passes and shoving them back.
 */
export default class Nova {
    constructor(scene, player, arsenal) {
        this.scene = scene;
        this.player = player;
        this.arsenal = arsenal;
        this.sinceMs = 0;
    }

    update(time, delta, enemies) {
        if (this.arsenal.nova === 0) {
            return;
        }

        this.sinceMs += delta;

        if (this.sinceMs >= novaInterval(this.arsenal.nova)) {
            this.sinceMs = 0;
            this.pulse(enemies);
        }
    }

    pulse(enemies) {
        const { scene, player } = this;
        const radius = novaRadius(this.arsenal.nova);

        // Crisp layered strokes, redrawn as the ring grows (a scaled texture goes soft).
        const ring = scene.add.graphics({ x: player.x, y: player.y }).setDepth(19);
        const wave = { radius: 20, alpha: 1 };
        scene.tweens.add({
            targets: wave,
            radius,
            alpha: 0,
            duration: EXPAND_MS,
            ease: 'Cubic.out',
            onUpdate: () => {
                ring.clear();
                [[28, 0.08], [14, 0.2], [6, 0.6], [2, 1]].forEach(([width, alpha], i) => {
                    ring.lineStyle(width, i === 3 ? 0xffffff : toInt(YELLOW), alpha * wave.alpha).strokeCircle(0, 0, wave.radius);
                });
            },
            onComplete: () => ring.destroy(),
        });
        scene.cameras.main.shake(120, 0.003);

        // Each rock is hit as the ring reaches it.
        within(player, enemies, radius).forEach((enemy) => {
            const distance = Phaser.Math.Distance.Between(player.x, player.y, enemy.x, enemy.y);

            scene.time.delayedCall((distance / radius) * EXPAND_MS * 0.5, () => {
                if (!enemy.active) {
                    return;
                }

                const away = new Phaser.Math.Vector2(enemy.x - player.x, enemy.y - player.y).normalize().scale(TUNING.novaKnockback);
                enemy.setPosition(enemy.x + away.x, enemy.y + away.y);
                scene.hitEnemy(enemy, scene.balance.bullet_damage * 2);
            });
        });
    }
}
