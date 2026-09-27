import { describe, expect, it } from 'vitest';
import { FaceQueue, faceKey } from './faces';

describe('FaceQueue', () => {
    it('hands out faces in turn and goes round again', () => {
        const queue = new FaceQueue([{ id: 'a' }, { id: 'b' }]);

        expect([queue.next(), queue.next(), queue.next()].map((face) => face.id)).toEqual(['a', 'b', 'a']);
    });

    it('returns null when there are no faces', () => {
        expect(new FaceQueue([]).next()).toBeNull();
    });
});

describe('faceKey', () => {
    it('keys textures by face id so later runs can reuse them', () => {
        expect(faceKey({ id: '123' })).toBe('face:123');
    });
});
