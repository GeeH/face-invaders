import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { CYAN, GREEN, MAGENTA, ORANGE, PURPLE, YELLOW, neonRect, neonStyle, toInt } from '../neon';
import { Vote } from '../vote';

const CARD_WIDTH = 440;
const CARD_HEIGHT = 250;
const CARD_GAP = 50;
const REVEAL_MS = 1400;

// Each card gets its own neon colour.
const CARD_COLOURS = [MAGENTA, GREEN, ORANGE, PURPLE, CYAN];

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

        const title = this.add.text(WIDTH / 2, 240, 'CHOOSE AN UPGRADE!', neonStyle(YELLOW, 80)).setOrigin(0.5);
        this.tweens.add({ targets: title, scale: { from: 1, to: 1.05 }, duration: 500, yoyo: true, repeat: -1, ease: 'Sine.inOut' });
        this.add
            .text(WIDTH / 2, 330, local ? `PRESS 1–${this.options.length} TO PICK` : 'CHAT VOTING IS COMING SOON, PICKING AT RANDOM', neonStyle(CYAN, 30, { weight: '700' }))
            .setOrigin(0.5);

        const rowWidth = this.options.length * CARD_WIDTH + (this.options.length - 1) * CARD_GAP;
        this.cards = this.options.map((option, i) =>
            this.card(WIDTH / 2 - rowWidth / 2 + CARD_WIDTH / 2 + i * (CARD_WIDTH + CARD_GAP), HEIGHT / 2 + 60, i + 1, option),
        );

        this.countdown = this.add.text(WIDTH / 2, HEIGHT / 2 + 250, '', neonStyle(MAGENTA, 56)).setOrigin(0.5);

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
        const colour = CARD_COLOURS[(number - 1) % CARD_COLOURS.length];
        const card = this.add.container(x, y);
        const background = neonRect(this.add.graphics(), CARD_WIDTH, CARD_HEIGHT, colour);

        const badge = this.add.circle(0, -CARD_HEIGHT / 2, 44, 0x07020f).setStrokeStyle(5, toInt(colour));
        const numberText = this.add.text(0, -CARD_HEIGHT / 2, `${number}`, neonStyle(colour, 54)).setOrigin(0.5);
        const name = this.add.text(0, -10, option.name.toUpperCase(), neonStyle(colour, 40)).setOrigin(0.5);
        const description = this.add.text(0, 60, option.describe(this.balance), neonStyle(CYAN, 26, { weight: '500' })).setOrigin(0.5);

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
                neonRect(card.background, CARD_WIDTH, CARD_HEIGHT, YELLOW);
            }

            this.tweens.add({ targets: card, scale: won ? 1.12 : 0.9, alpha: won ? 1 : 0.25, duration: 300, ease: 'Back.out' });
        });

        this.time.delayedCall(REVEAL_MS, () => {
            this.onDecided(winner);
            this.scene.stop();
        });
    }
}
