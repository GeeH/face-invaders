import { describe, expect, it } from 'vitest';
import { Vote } from './vote';

const options = ['attack', 'heal', 'max'];

describe('Vote', () => {
    it('counts one vote per viewer, keeping their latest', () => {
        const vote = new Vote(options, 1000);

        vote.castVote('alice', 1);
        vote.castVote('bob', 2);
        vote.castVote('alice', 2);

        expect(vote.tally()).toEqual([0, 2, 0]);
    });

    it('ignores votes for options that do not exist', () => {
        const vote = new Vote(options, 1000);

        expect(vote.castVote('alice', 0)).toBe(false);
        expect(vote.castVote('alice', 4)).toBe(false);
        expect(vote.castVote('alice', 1.5)).toBe(false);
        expect(vote.tally()).toEqual([0, 0, 0]);
    });

    it('picks the most voted option when time runs out', () => {
        const vote = new Vote(options, 1000);
        vote.castVote('a', 3);
        vote.castVote('b', 3);
        vote.castVote('c', 1);

        expect(vote.update(999)).toBeNull();
        expect(vote.update(1)).toBe('max');
        expect(vote.finished).toBe(true);
    });

    it('breaks a tie randomly between the leaders only', () => {
        const picks = new Set();

        for (const roll of [0, 0.99]) {
            const vote = new Vote(options, 10, () => roll);
            vote.castVote('a', 1);
            vote.castVote('b', 3);
            picks.add(vote.update(10));
        }

        expect(picks).toEqual(new Set(['attack', 'max']));
    });

    it('picks at random from every option when nobody votes', () => {
        const picks = new Set([0, 0.5, 0.99].map((roll) => new Vote(options, 10, () => roll).update(10)));

        expect(picks).toEqual(new Set(options));
    });

    it('lets the streamer pick, ending the vote immediately', () => {
        const vote = new Vote(options, 30_000);
        vote.castVote('a', 1);

        expect(vote.pick(2)).toBe('heal');
        expect(vote.finished).toBe(true);
        expect(vote.update(30_000)).toBe('heal');
        expect(vote.castVote('b', 3)).toBe(false);
    });

    it('ignores a pick for an option that does not exist', () => {
        const vote = new Vote(options, 1000);

        expect(vote.pick(9)).toBeNull();
        expect(vote.finished).toBe(false);
    });
});
