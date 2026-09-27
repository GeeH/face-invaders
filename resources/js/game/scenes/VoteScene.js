import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { Vote } from '../vote';

const CARD_WIDTH = 440;
const CARD_HEIGHT = 250;
const CARD_GAP = 50;
const REVEAL_MS = 1400;

const TEXT = {
    fontFamily: 'system-ui, sans-serif',
    color: '#ffffff',
    stroke: '#000000',
    strokeThickness: 8,
    align: 'center',
};

/**
 * The between-waves upgrade vote: numbered option cards and a countdown.
 * Launched over the game by GameScene, which gets the winner back through
 * the onDecided callback.
 *
 * With ?local-vote (or ?debug) keys 1–N pick straight away. Otherwise the
 * vote runs out and picks at random until chat votes arrive (#23, #24).
 */
export default class VoteScene extends Phaser.Scene {
    constructor() {
        super('vote');
    }

    init({ options, balance, seconds, onDecided }) {
        this.options = options;
        this.balance = balance;
        this.onDecided = onDecided;
        this.vote = new Vote(options, seconds * 1000);
        this.revealed = false;
    }

    create() {
        const local = this.registry.get('localVote');

        this.add.text(WIDTH / 2, 250, 'Choose an upgrade!', { ...TEXT, fontSize: '72px', fontStyle: 'bold' }).setOrigin(0.5);
        this.add
            .text(WIDTH / 2, 330, local ? `Press 1–${this.options.length} to pick` : 'Chat voting is coming soon, picking at random', {
                ...TEXT,
                fontSize: '34px',
                strokeThickness: 6,
            })
            .setOrigin(0.5);

        const rowWidth = this.options.length * CARD_WIDTH + (this.options.length - 1) * CARD_GAP;
        this.cards = this.options.map((option, i) =>
            this.card(WIDTH / 2 - rowWidth / 2 + CARD_WIDTH / 2 + i * (CARD_WIDTH + CARD_GAP), HEIGHT / 2 + 60, i + 1, option),
        );

        this.countdown = this.add.text(WIDTH / 2, HEIGHT / 2 + 240, '', { ...TEXT, fontSize: '40px', fontStyle: 'bold' }).setOrigin(0.5);

        if (local) {
            this.input.keyboard.on('keydown', (event) => {
                const choice = Number(event.key);

                if (Number.isInteger(choice)) {
                    this.vote.pick(choice);
                }
            });
        }
    }

    update(time, delta) {
        const winner = this.vote.update(delta);

        this.countdown.setText(this.vote.finished ? '' : `${Math.ceil(this.vote.remainingMs / 1000)}s`);

        if (winner && !this.revealed) {
            this.reveal(winner);
        }
    }

    card(x, y, number, option) {
        const card = this.add.container(x, y);
        const background = this.add
            .rectangle(0, 0, CARD_WIDTH, CARD_HEIGHT, 0x111827, 0.88)
            .setStrokeStyle(6, 0x60a5fa);

        const badge = this.add.circle(0, -CARD_HEIGHT / 2, 44, 0x2563eb).setStrokeStyle(6, 0xffffff);
        const numberText = this.add.text(0, -CARD_HEIGHT / 2, `${number}`, { ...TEXT, fontSize: '54px', fontStyle: 'bold', strokeThickness: 0 }).setOrigin(0.5);
        const name = this.add.text(0, -10, option.name, { ...TEXT, fontSize: '46px', fontStyle: 'bold', strokeThickness: 0 }).setOrigin(0.5);
        const description = this.add
            .text(0, 60, option.describe(this.balance), { ...TEXT, fontSize: '30px', color: '#cbd5e1', strokeThickness: 0 })
            .setOrigin(0.5);

        card.add([background, badge, numberText, name, description]);
        card.option = option;
        card.background = background;

        return card;
    }

    /**
     * Highlight the winner, fade the rest, then hand the upgrade back to the game.
     */
    reveal(winner) {
        this.revealed = true;

        this.cards.forEach((card) => {
            const won = card.option === winner;

            if (won) {
                card.background.setStrokeStyle(8, 0xfacc15);
            }

            this.tweens.add({ targets: card, scale: won ? 1.12 : 0.9, alpha: won ? 1 : 0.25, duration: 300, ease: 'Back.out' });
        });

        this.time.delayedCall(REVEAL_MS, () => {
            this.onDecided(winner);
            this.scene.stop();
        });
    }
}
