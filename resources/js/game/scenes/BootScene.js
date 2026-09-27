import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { fetchRun } from '../run';

const RETRY_SECONDS = 5;

/**
 * Loads the run from Laravel, then hands over to the game. If the server
 * can't be reached it keeps retrying, so OBS recovers on its own.
 */
export default class BootScene extends Phaser.Scene {
    constructor() {
        super('boot');
    }

    create() {
        this.status = this.add
            .text(WIDTH / 2, HEIGHT - 60, '', {
                fontFamily: 'system-ui, sans-serif',
                fontSize: '28px',
                color: '#ffffff',
                stroke: '#000000',
                strokeThickness: 6,
            })
            .setOrigin(0.5);

        this.startRun();
    }

    async startRun() {
        try {
            const run = await fetchRun(this.registry.get('runUrl'));
            this.registry.set('run', run);
            this.scene.start('game');
        } catch (error) {
            console.error('[face-invaders]', error);
            this.status.setText(`Can't reach Face Invaders, retrying in ${RETRY_SECONDS}s…`);
            this.time.delayedCall(RETRY_SECONDS * 1000, () => this.startRun());
        }
    }
}
