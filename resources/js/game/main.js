import Phaser from 'phaser';
import { HEIGHT, WIDTH } from './config';
import BootScene from './scenes/BootScene';
import GameScene from './scenes/GameScene';

const parent = document.getElementById('game');

new Phaser.Game({
    type: Phaser.AUTO,
    parent,
    width: WIDTH,
    height: HEIGHT,
    // Transparent so the game sits on top of the stream as an OBS overlay.
    transparent: true,
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
