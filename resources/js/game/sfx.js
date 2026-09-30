// Synthy retro-arcade sound effects (#73), made live with the Web Audio API:
// oscillators with pitch sweeps, and filtered noise for explosions. There
// are no audio files, so every sound can be tweaked right here.
//
// Sounds play through Phaser's sound manager, which unlocks audio on the
// first click or key press (browsers block it until then), mutes with
// game.sound.mute, and pauses when the tab is hidden.

// Fewer of the same sound than this many ms apart are dropped, so a
// multishot fan or a chain reaction doesn't turn into a wall of noise.
const MIN_GAP_MS = {
    shot: 45,
    drone: 70,
    hit: 35,
    explode: 45,
    blast: 70,
    zap: 60,
    blade: 50,
    tick: 0,
};

const MUTED_KEY = 'face-invaders:muted';

/**
 * Each sound is a list of layers: tones (an oscillator sweeping from one
 * pitch to another) and noise (white noise through a sweeping filter).
 * Times are in seconds, volumes 0–1 before the master gain.
 */
const SOUNDS = {
    // Player laser: a classic falling "pew".
    shot: [{ tone: 'square', from: 880, to: 220, duration: 0.09, volume: 0.12 }],
    // Drones: smaller, higher pew.
    drone: [{ tone: 'square', from: 1400, to: 500, duration: 0.07, volume: 0.06 }],
    // A rock takes a hit but survives.
    hit: [
        { noise: 'highpass', from: 3000, to: 3000, duration: 0.04, volume: 0.12 },
        { tone: 'triangle', from: 600, to: 300, duration: 0.05, volume: 0.08 },
    ],
    // A rock blows up.
    explode: [
        { noise: 'lowpass', from: 2400, to: 150, duration: 0.35, volume: 0.35 },
        { tone: 'sine', from: 140, to: 40, duration: 0.3, volume: 0.3 },
    ],
    // Explosive rounds: a deeper, longer boom.
    blast: [
        { noise: 'lowpass', from: 1400, to: 60, duration: 0.6, volume: 0.45 },
        { tone: 'sine', from: 100, to: 28, duration: 0.55, volume: 0.4 },
    ],
    // Chain lightning: a buzzy crackle.
    zap: [
        { tone: 'sawtooth', from: 1800, to: 180, duration: 0.16, volume: 0.1 },
        { noise: 'bandpass', from: 5000, to: 1200, duration: 0.16, volume: 0.2, q: 4 },
    ],
    // Nova pulse: a rising whoosh over a falling thump.
    nova: [
        { noise: 'bandpass', from: 250, to: 3500, duration: 0.5, volume: 0.35, q: 1.5 },
        { tone: 'sine', from: 240, to: 50, duration: 0.5, volume: 0.3 },
    ],
    // Orbiting blade strikes: a metallic ting.
    blade: [
        { tone: 'triangle', from: 2600, to: 2000, duration: 0.07, volume: 0.08 },
        { tone: 'square', from: 3900, to: 3900, duration: 0.03, volume: 0.025 },
    ],
    // The ship takes damage: a nasty downward buzz.
    hurt: [
        { tone: 'sawtooth', from: 320, to: 55, duration: 0.45, volume: 0.28 },
        { noise: 'lowpass', from: 900, to: 200, duration: 0.25, volume: 0.25 },
    ],
    // Game over: a sad descending run into a long low drone.
    gameOver: [
        ...arpeggio([523, 440, 349, 294, 220], { tone: 'square', step: 0.17, duration: 0.2, volume: 0.13 }),
        { tone: 'sawtooth', from: 110, to: 36, duration: 1.4, volume: 0.22, delay: 0.85 },
    ],
    // A wave starts: quick rising arpeggio.
    waveStart: arpeggio([262, 330, 392, 523], { tone: 'square', step: 0.07, duration: 0.09, volume: 0.1 }),
    // A wave is cleared: a little fanfare.
    waveCleared: [
        ...arpeggio([523, 659, 784, 1047], { tone: 'square', step: 0.08, duration: 0.1, volume: 0.1 }),
        ...arpeggio([784, 1047], { tone: 'triangle', step: 0.14, duration: 0.3, volume: 0.14, delay: 0.36 }),
    ],
    // The upgrade vote opens: a two-note chime.
    voteOpen: arpeggio([880, 1320], { tone: 'triangle', step: 0.12, duration: 0.35, volume: 0.14 }),
    // A vote comes in, or the last seconds count down.
    tick: [{ tone: 'square', from: 1000, to: 1000, duration: 0.03, volume: 0.05 }],
    // The winning upgrade is revealed: a sparkly run up.
    reveal: arpeggio([1047, 1319, 1568, 2093], { tone: 'triangle', step: 0.05, duration: 0.14, volume: 0.12 }),
    // The upgrade is applied: a power-up sweep.
    powerUp: [
        { tone: 'square', from: 200, to: 1600, duration: 0.35, volume: 0.12 },
        { tone: 'triangle', from: 400, to: 3200, duration: 0.35, volume: 0.1 },
    ],
};

function arpeggio(notes, { tone, step, duration, volume, delay = 0 }) {
    return notes.map((note, i) => ({ tone, from: note, to: note, duration, volume, delay: delay + i * step }));
}

/**
 * Drops repeats of the same sound that come too close together.
 */
export class Throttle {
    constructor(gaps = MIN_GAP_MS) {
        this.gaps = gaps;
        this.lastPlayed = new Map();
    }

    allow(name, nowMs) {
        const gap = this.gaps[name] ?? 0;
        const last = this.lastPlayed.get(name);

        if (gap > 0 && last !== undefined && nowMs - last < gap) {
            return false;
        }

        this.lastPlayed.set(name, nowMs);

        return true;
    }
}

export const soundNames = () => Object.keys(SOUNDS);

let sound = null;
let output = null;
let noiseBuffer = null;
const throttle = new Throttle();

/**
 * Hook the synth up to the game's sound manager, restoring the viewer's
 * mute choice. Does nothing where Web Audio isn't available.
 */
export function initSfx(game, { muted = false } = {}) {
    if (!game.sound?.context) {
        return;
    }

    sound = game.sound;
    const ctx = sound.context;

    // A compressor keeps many overlapping sounds from clipping.
    const compressor = ctx.createDynamicsCompressor();
    output = ctx.createGain();
    output.gain.value = 0.6;
    output.connect(compressor).connect(sound.destination);

    noiseBuffer = ctx.createBuffer(1, ctx.sampleRate, ctx.sampleRate);
    const samples = noiseBuffer.getChannelData(0);
    for (let i = 0; i < samples.length; i++) {
        samples[i] = Math.random() * 2 - 1;
    }

    sound.mute = muted || readMuted();
}

/**
 * Play a named sound effect. Safe to call before audio is unlocked or
 * where there's no audio at all: it just doesn't play.
 */
export function play(name) {
    if (!sound || sound.locked || sound.mute || !SOUNDS[name]) {
        return;
    }

    const ctx = sound.context;

    if (!throttle.allow(name, ctx.currentTime * 1000)) {
        return;
    }

    SOUNDS[name].forEach((layer) => (layer.tone ? playTone(ctx, layer) : playNoise(ctx, layer)));
}

export function isMuted() {
    return Boolean(sound?.mute);
}

/**
 * Whether the browser is still waiting for a click or key press before it allows sound.
 */
export function isLocked() {
    return Boolean(sound?.locked) && !sound?.unlocked;
}

export function toggleMute() {
    if (!sound) {
        return false;
    }

    sound.mute = !sound.mute;

    try {
        window.localStorage.setItem(MUTED_KEY, sound.mute ? '1' : '0');
    } catch {
        // Storage can be blocked; muting still works for this session.
    }

    return sound.mute;
}

function readMuted() {
    try {
        return window.localStorage.getItem(MUTED_KEY) === '1';
    } catch {
        return false;
    }
}

function envelope(ctx, start, duration, volume) {
    const gain = ctx.createGain();
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(volume, start + 0.005);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
    gain.connect(output);

    return gain;
}

function playTone(ctx, { tone, from, to, duration, volume, delay = 0 }) {
    const start = ctx.currentTime + delay;
    const oscillator = ctx.createOscillator();

    oscillator.type = tone;
    oscillator.frequency.setValueAtTime(from, start);
    if (to !== from) {
        oscillator.frequency.exponentialRampToValueAtTime(to, start + duration);
    }

    oscillator.connect(envelope(ctx, start, duration, volume));
    oscillator.start(start);
    oscillator.stop(start + duration + 0.02);
}

function playNoise(ctx, { noise, from, to, duration, volume, q = 1, delay = 0 }) {
    const start = ctx.currentTime + delay;
    const source = ctx.createBufferSource();
    const filter = ctx.createBiquadFilter();

    source.buffer = noiseBuffer;
    filter.type = noise;
    filter.Q.value = q;
    filter.frequency.setValueAtTime(from, start);
    if (to !== from) {
        filter.frequency.exponentialRampToValueAtTime(to, start + duration);
    }

    source.connect(filter).connect(envelope(ctx, start, duration, volume));
    source.start(start);
    source.stop(start + duration + 0.02);
}
