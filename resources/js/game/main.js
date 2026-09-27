import Phaser from 'phaser';
import { HEIGHT, WIDTH } from './config';
import BootScene from './scenes/BootScene';
import GameScene from './scenes/GameScene';

const parent = document.getElementById('game');

// Add ?debug to the game URL to see physics hitboxes. It also drives the game loop with
// timers instead of requestAnimationFrame, so it keeps running in headless browsers.
const debug = new URLSearchParams(window.location.search).has('debug');

const game = new Phaser.Game({
    type: Phaser.AUTO,
    parent,
    width: WIDTH,
    height: HEIGHT,
    // Transparent so the game sits on top of the stream as an OBS overlay.
    transparent: true,
    physics: {
        default: 'arcade',
        arcade: { debug },
    },
    fps: debug ? { forceSetTimeOut: true, target: 60 } : {},
    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH,
    },
    scene: [BootScene, GameScene],
    callbacks: {
        preBoot: (game) => {
            game.registry.set('streamer', parent.dataset.streamer);
            game.registry.set('runUrl', parent.dataset.runUrl);
        },
    },
});

if (debug) {
    window.faceInvaders = game;
}
