import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';

/**
 * The main game. For now it only shows that the game is running; the base,
 * turret, enemies and waves arrive in #13 to #15.
 */
export default class GameScene extends Phaser.Scene {
    constructor() {
        super('game');
    }

    create() {
        const streamer = this.registry.get('streamer');

        const title = this.add
            .text(WIDTH / 2, HEIGHT / 2, `Face Invaders\n${streamer}`, {
                fontFamily: 'system-ui, sans-serif',
                fontSize: '72px',
                fontStyle: 'bold',
                color: '#ffffff',
                align: 'center',
                stroke: '#000000',
                strokeThickness: 8,
            })
            .setOrigin(0.5);

        // Fade the title out so it doesn't sit on top of the stream.
        this.tweens.add({ targets: title, alpha: 0, delay: 3000, duration: 1000 });
    }
}
