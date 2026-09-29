import Phaser from 'phaser';
import { HEIGHT, WIDTH } from './config';
import { listen } from './live';
import BootScene from './scenes/BootScene';
import GameScene from './scenes/GameScene';
import HudScene from './scenes/HudScene';
import VoteScene from './scenes/VoteScene';

const parent = document.getElementById('game');

// Add ?debug to the game URL to see physics hitboxes. It also drives the game loop with
// timers instead of requestAnimationFrame, so it keeps running in headless browsers.
const params = new URLSearchParams(window.location.search);
const debug = params.has('debug');

// ?local-vote lets keys 1–N pick the upgrade (for development and demos);
// ?vote-seconds=N shortens the voting window while testing.

const game = new Phaser.Game({
    type: Phaser.AUTO,
    parent,
    width: WIDTH,
    height: HEIGHT,
    // Black, like an arcade screen, so the neon glows as it should.
    backgroundColor: '#000000',
    physics: {
        default: 'arcade',
        arcade: { debug },
    },
    fps: debug ? { forceSetTimeOut: true, target: 60 } : {},
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
    scene: [BootScene, GameScene, HudScene, VoteScene],
    callbacks: {
        preBoot: (game) => {
            game.registry.set('streamer', parent.dataset.streamer);
            game.registry.set('runUrl', parent.dataset.runUrl);
            game.registry.set('localVote', debug || params.has('local-vote'));
            game.registry.set('voteSeconds', Number(params.get('vote-seconds')) || null);
        },
    },
});

// Live events from Laravel are passed on as game events for whichever scene cares.
listen(parent.dataset.channel, {
    ping: (payload) => game.events.emit('ping', payload),
});

if (debug) {
    window.faceInvaders = game;
}
