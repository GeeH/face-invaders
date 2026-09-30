import Phaser from 'phaser';
import { HEIGHT, WIDTH } from '../config';
import { CYAN, GREEN, MAGENTA, ORANGE, PURPLE, YELLOW, neonRect, neonStyle, toInt } from '../neon';
import { openVote, pickVote } from '../run';
import { play } from '../sfx';
import { Vote } from '../vote';

const CARD_WIDTH = 440;
const CARD_HEIGHT = 290;
const CARD_GAP = 50;
const BAR_WIDTH = CARD_WIDTH - 80;
const REVEAL_MS = 1400;

// How long after the window to wait for Laravel's result before deciding
// locally (it's usually a second or two, while the queue closes the vote).
const RESULT_GRACE_MS = 6000;

// Each card gets its own neon colour.
const CARD_COLOURS = [MAGENTA, GREEN, ORANGE, PURPLE, CYAN];

/**
 * The between-waves upgrade vote: numbered option cards, live vote counts
 * and a countdown. Launched over the game by GameScene, which gets the
 * winner back through the onDecided callback.
 *
 * Chat votes by default: this opens the vote on Laravel (#23) and follows
 * it over Reverb, so chat's `!vote N` decides. If the server can't be
 * reached, or its result never arrives, the game decides from what it has.
 *
 * With ?local-vote (or ?debug) keys 1–N pick straight away instead. Outside
 * OBS they pick during a chat vote too, closing the server's vote with the
 * same upgrade.
 */
export default class VoteScene extends Phaser.Scene {
    constructor() {
        super('vote');
    }

    init({ options, balance, wave, seconds, onDecided }) {
        this.options = options;
        this.balance = balance;
        this.wave = wave;
        this.onDecided = onDecided;
        this.local = this.registry.get('localVote');
        this.keyPick = this.registry.get('keyPick');
        this.graceMs = this.local ? 0 : RESULT_GRACE_MS;
        this.vote = new Vote(options, seconds * 1000 + this.graceMs);
        this.sessionId = null;
        this.revealed = false;
    }

    create() {
        const title = this.add.text(WIDTH / 2, 190, 'CHOOSE AN UPGRADE!', neonStyle(YELLOW, 80)).setOrigin(0.5);
        this.tweens.add({ targets: title, scale: { from: 1, to: 1.05 }, duration: 500, yoyo: true, repeat: -1, ease: 'Sine.inOut' });

        const prompt = this.local ? `PRESS 1–${this.options.length} TO PICK` : `TYPE  !vote 1–${this.options.length}  IN CHAT`;
        this.prompt = this.add.text(WIDTH / 2, 290, prompt, neonStyle(this.local ? CYAN : GREEN, this.local ? 30 : 44)).setOrigin(0.5);

        if (this.keyPick && !this.local) {
            this.keyHint = this.add.text(WIDTH / 2, 345, `OR PRESS 1–${this.options.length} TO PICK`, neonStyle(CYAN, 26)).setOrigin(0.5);
        }

        const rowWidth = this.options.length * CARD_WIDTH + (this.options.length - 1) * CARD_GAP;
        this.cards = this.options.map((option, i) =>
            this.card(WIDTH / 2 - rowWidth / 2 + CARD_WIDTH / 2 + i * (CARD_WIDTH + CARD_GAP), HEIGHT / 2 + 60, i + 1, option),
        );

        this.countdown = this.add.text(WIDTH / 2, HEIGHT / 2 + 280, '', neonStyle(MAGENTA, 56)).setOrigin(0.5);

        play('voteOpen');
        this.lastSecond = null;

        if (this.keyPick) {
            this.input.keyboard.on('keydown', (event) => this.pick(Number(event.key)));
        }

        if (!this.local) {
            this.followChat();
        }
    }

    /**
     * Open the vote on Laravel, then show its tallies and winner as they
     * arrive. Events for any other vote (another copy of the game) are ignored.
     */
    followChat() {
        const ours = (handler) => (payload) => payload.id === this.sessionId && handler(payload);
        const handlers = {
            'vote.tally': ours(({ tally }) => this.showTally(tally)),
            'vote.closed': ours(({ tally, winner }) => {
                this.showTally(tally);
                this.vote.settle(this.options.find((option) => option.id === winner));
            }),
        };

        Object.entries(handlers).forEach(([event, handler]) => this.game.events.on(event, handler));
        this.events.once('shutdown', () => Object.entries(handlers).forEach(([event, handler]) => this.game.events.off(event, handler)));

        openVote(this.registry.get('voteUrl'), {
            wave: this.wave,
            options: this.options.map((option) => ({ id: option.id, name: option.name, description: option.describe(this.balance) })),
        })
            .then(({ id }) => {
                this.sessionId = id;

                // Picked with a key before the server answered: close its vote to match.
                if (this.vote.finished) {
                    pickVote(`${this.registry.get('voteUrl')}/${id}/pick`, this.vote.winner.id).catch((error) => console.error('[face-invaders]', error));
                }
            })
            .catch((error) => {
                console.error('[face-invaders]', error);
                this.prompt.setText('CHAT VOTE OFFLINE, PICKING AT RANDOM').setStyle(neonStyle(CYAN, 30));
            });
    }

    /**
     * The streamer picks option N with the keyboard. During a chat vote the
     * server's vote is closed with the same upgrade.
     */
    pick(choice) {
        const winner = Number.isInteger(choice) ? this.vote.pick(choice) : null;

        if (!winner || this.sessionId === null) {
            return;
        }

        pickVote(`${this.registry.get('voteUrl')}/${this.sessionId}/pick`, winner.id).catch((error) => console.error('[face-invaders]', error));
    }

    update(time, delta) {
        const winner = this.vote.update(delta);
        const secondsLeft = Math.ceil(Math.max(0, this.vote.remainingMs - this.graceMs) / 1000);

        this.countdown.setText(this.vote.finished ? '' : secondsLeft > 0 ? `${secondsLeft}s` : 'COUNTING VOTES…');

        // Tick through the last few seconds.
        if (!this.vote.finished && secondsLeft !== this.lastSecond && secondsLeft > 0 && secondsLeft <= 5) {
            play('tick');
        }
        this.lastSecond = secondsLeft;

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
        const name = this.add.text(0, -40, option.name.toUpperCase(), neonStyle(colour, 40)).setOrigin(0.5);
        const description = this.add.text(0, 25, option.describe(this.balance), neonStyle(CYAN, 26, { weight: '500' })).setOrigin(0.5);

        // Long names and descriptions shrink to fit rather than spill out.
        [name, description].forEach((text) => text.setScale(Math.min(1, (CARD_WIDTH - 50) / (text.width - text.padding.left * 2))));

        card.add([background, badge, numberText, name, description]);

        if (!this.local) {
            card.bar = this.add.rectangle(-BAR_WIDTH / 2, 85, 0, 14, toInt(colour)).setOrigin(0, 0.5);
            card.votes = this.add.text(0, 118, '0 VOTES', neonStyle(colour, 24, { weight: '700' })).setOrigin(0.5);
            card.add([this.add.rectangle(0, 85, BAR_WIDTH, 14, 0xffffff, 0.08), card.bar, card.votes]);
        }

        card.option = option;
        card.background = background;

        return card;
    }

    /**
     * Grow each card's bar to its share of the votes so far.
     *
     * @param {Record<number, number>} tally votes keyed by option number
     */
    showTally(tally) {
        play('tick');
        this.vote.useTally(tally);

        const counts = this.vote.tally();
        const total = counts.reduce((sum, count) => sum + count, 0);

        this.cards.forEach((card, i) => {
            const previous = card.votes.text;
            card.votes.setText(`${counts[i]} ${counts[i] === 1 ? 'VOTE' : 'VOTES'}`);
            this.tweens.add({ targets: card.bar, width: total ? (BAR_WIDTH * counts[i]) / total : 0, duration: 250, ease: 'Cubic.out' });

            if (card.votes.text !== previous) {
                this.tweens.add({ targets: card, scale: { from: 1.06, to: 1 }, duration: 200, ease: 'Back.out' });
            }
        });
    }

    /**
     * Highlight the winner, fade the rest, then hand the upgrade back to the game.
     */
    reveal(winner) {
        this.revealed = true;
        play('reveal');
        this.prompt.setText('');
        this.keyHint?.setText('');

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
