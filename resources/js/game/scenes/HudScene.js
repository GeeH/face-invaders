import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { faceKey } from '../faces';
import { CYAN, GREEN, MAGENTA, RED, YELLOW, neonStyle, toInt } from '../neon';

const LIFE_GAP = 4;
const QUEUE_TOP = 200;
const QUEUE_ROW = 84;
const AVATAR_SIZE = 60;

/**
 * Score, wave, lives and who's coming next, drawn over the game. It only
 * reads the registry, which GameScene keeps up to date.
 */
export default class HudScene extends Phaser.Scene {
    constructor() {
        super('hud');
    }

    create() {
        this.lives = [];
        this.shownScore = null;

        this.score = this.add.text(30, 58, '', neonStyle(GREEN, 50)).setOrigin(0, 0);
        this.wave = this.add.text(WIDTH / 2, HEIGHT - 20, '', neonStyle(CYAN, 44)).setOrigin(0.5, 1);
        this.labels = [
            this.add.text(30, 20, 'SCORE', neonStyle(CYAN, 30)).setOrigin(0, 0),
            this.add.text(WIDTH - 30, 20, 'LIVES', neonStyle(CYAN, 30)).setOrigin(1, 0),
            this.add.text(30, QUEUE_TOP, 'INCOMING', neonStyle(MAGENTA, 26)).setOrigin(0, 0),
            this.wave,
        ];
        this.queue = [];

        this.registry.events.on('changedata', this.refresh, this);
        this.events.once('shutdown', () => this.registry.events.off('changedata', this.refresh, this));
        this.refresh();

        // An occasional flicker keeps the labels feeling like real neon tubes.
        this.time.addEvent({
            delay: 120,
            loop: true,
            callback: () => this.labels.forEach((label) => label.setAlpha(Math.random() < 0.02 ? 0.4 : 1)),
        });
    }

    refresh() {
        this.drawLives(this.registry.get('health') ?? 0, this.registry.get('maxHealth') ?? 0);
        this.wave.setText(`WAVE ${this.registry.get('wave') ?? 1}`);
        this.drawScore(this.registry.get('score') ?? 0);
        this.drawQueue(this.registry.get('queue') ?? []);
    }

    drawScore(score) {
        if (score === this.shownScore) {
            return;
        }

        const bump = this.shownScore !== null;
        this.shownScore = score;
        this.score.setText(score.toLocaleString());

        if (bump) {
            this.tweens.add({ targets: this.score, scale: { from: 1.25, to: 1 }, duration: 220, ease: 'Back.out' });
        }
    }

    /**
     * One mini ship per point of max health; lost ones go dim and red.
     */
    drawLives(health, maxHealth) {
        while (this.lives.length < maxHealth) {
            this.lives.push(this.add.image(0, 0, 'neon-life').setOrigin(1, 0));
        }

        this.lives.forEach((life, i) => {
            const alive = i < health;

            life.setPosition(WIDTH - 20 - i * (life.displayWidth + LIFE_GAP), 62)
                .setVisible(i < maxHealth)
                .setTint(alive ? 0xffffff : toInt(RED))
                .setAlpha(alive ? 1 : 0.3);
        });
    }

    /**
     * The next few followers' avatars and names, first in line at the top.
     */
    drawQueue(faces) {
        while (this.queue.length < faces.length) {
            this.queue.push(this.queueRow(this.queue.length));
        }

        this.queue.forEach((row, i) => {
            const face = faces[i];
            row.setVisible(Boolean(face));

            if (!face) {
                return;
            }

            const hasAvatar = this.textures.exists(faceKey(face));
            row.avatar.setTexture(hasAvatar ? faceKey(face) : '__DEFAULT').setDisplaySize(AVATAR_SIZE, AVATAR_SIZE).setVisible(hasAvatar);
            row.name.setText(face.name);
        });
    }

    queueRow(i) {
        const colour = i === 0 ? YELLOW : MAGENTA;
        const y = QUEUE_TOP + 100 + i * QUEUE_ROW;
        const ring = this.add.graphics();

        [[12, 0.12], [6, 0.35], [3, 1]].forEach(([width, alpha]) => {
            ring.lineStyle(width, toInt(colour), alpha).strokeCircle(0, 0, AVATAR_SIZE / 2 + 3);
        });

        const row = this.add.container(30 + AVATAR_SIZE / 2 + 6, y, [
            this.add.image(0, 0, '__DEFAULT'),
            ring,
            this.add.text(AVATAR_SIZE / 2 + 14, 0, '', neonStyle(colour, i === 0 ? 28 : 22, { weight: '700' })).setOrigin(0, 0.5),
        ]);
        [row.avatar, , row.name] = row.list;
        row.setAlpha(1 - i * 0.12);

        return row;
    }
}
