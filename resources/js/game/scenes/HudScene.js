import Phaser from 'phaser';
import { lifeIconFor } from '../assets';
import { WIDTH } from '../config';

const TEXT = {
    fontFamily: 'system-ui, sans-serif',
    fontSize: '36px',
    fontStyle: 'bold',
    color: '#ffffff',
    stroke: '#000000',
    strokeThickness: 7,
};

const LIFE_SCALE = 1.6;
const LIFE_GAP = 8;

/**
 * Lives, wave and score, drawn over the game. It only reads the registry,
 * which GameScene keeps up to date.
 */
export default class HudScene extends Phaser.Scene {
    constructor() {
        super('hud');
    }

    create() {
        this.lives = [];
        this.wave = this.add.text(WIDTH - 40, 30, '', TEXT).setOrigin(1, 0);
        this.score = this.add.text(WIDTH - 40, 80, '', TEXT).setOrigin(1, 0);

        this.registry.events.on('changedata', this.refresh, this);
        this.events.once('shutdown', () => this.registry.events.off('changedata', this.refresh, this));
        this.refresh();
    }

    refresh() {
        this.drawLives(this.registry.get('health') ?? 0, this.registry.get('maxHealth') ?? 0);
        this.wave.setText(`Wave ${this.registry.get('wave') ?? 1}`);
        this.score.setText(`${(this.registry.get('score') ?? 0).toLocaleString()} pts`);
    }

    /**
     * One mini ship per point of max health; lost ones are faded out.
     */
    drawLives(health, maxHealth) {
        const ship = this.registry.get('ship');

        if (!ship) {
            return;
        }

        while (this.lives.length < maxHealth) {
            this.lives.push(this.add.image(0, 0, lifeIconFor(ship)).setOrigin(0, 0).setScale(LIFE_SCALE));
        }

        this.lives.forEach((life, i) => {
            life.setTexture(lifeIconFor(ship))
                .setPosition(40 + i * (life.displayWidth + LIFE_GAP), 36)
                .setVisible(i < maxHealth)
                .setAlpha(i < health ? 1 : 0.25);
        });
    }
}
