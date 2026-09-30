import { describe, expect, it } from 'vitest';
import { Throttle, play, soundNames } from './sfx';

describe('Throttle', () => {
    it('drops repeats of a sound that come too close together', () => {
        const throttle = new Throttle({ shot: 50 });

        expect(throttle.allow('shot', 0)).toBe(true);
        expect(throttle.allow('shot', 30)).toBe(false);
        expect(throttle.allow('shot', 60)).toBe(true);
    });

    it('throttles each sound on its own', () => {
        const throttle = new Throttle({ shot: 50, hit: 50 });

        expect(throttle.allow('shot', 0)).toBe(true);
        expect(throttle.allow('hit', 10)).toBe(true);
    });

    it('never drops sounds without a gap', () => {
        const throttle = new Throttle({});

        expect(throttle.allow('gameOver', 0)).toBe(true);
        expect(throttle.allow('gameOver', 0)).toBe(true);
    });
});

describe('play', () => {
    it('is safe to call before sound is set up', () => {
        expect(() => play('shot')).not.toThrow();
    });

    it('has every sound the game plays', () => {
        expect(soundNames()).toEqual(
            expect.arrayContaining(['shot', 'drone', 'hit', 'explode', 'blast', 'zap', 'nova', 'blade', 'hurt', 'gameOver', 'waveStart', 'waveCleared', 'voteOpen', 'tick', 'reveal', 'powerUp']),
        );
    });
});
