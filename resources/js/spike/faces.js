// Spike for #10: can Phaser load Twitch avatars straight from Twitch's CDN and draw them with WebGL?
import Phaser from 'phaser';

const faces = JSON.parse(document.getElementById('faces').textContent);
const log = (...args) => console.log('[faces-spike]', ...args);

const WIDTH = 1920;
const HEIGHT = 1080;
const SIZE = 150;
const COLUMNS = 10;

class FacesScene extends Phaser.Scene {
    constructor() {
        super('faces');
        this.failed = [];
    }

    preload() {
        // Twitch's CDN sends Access-Control-Allow-Origin: *, so anonymous CORS keeps WebGL textures untainted.
        this.load.setCORS('anonymous');

        faces.forEach((face, i) => {
            if (face.avatar.endsWith('.svg')) {
                this.load.svg(`face-${i}`, face.avatar, { width: 256, height: 256 });
            } else {
                this.load.image(`face-${i}`, face.avatar);
            }
        });

        this.load.on('loaderror', (file) => {
            this.failed.push(file.src);
            log('failed to load', file.src);
        });
    }

    create() {
        // A white circle shared by every avatar as its mask.
        this.add.graphics().fillStyle(0xffffff).fillCircle(128, 128, 128).generateTexture('circle', 256, 256).destroy();

        const rows = Math.ceil(faces.length / COLUMNS);
        const gapX = WIDTH / COLUMNS;
        const gapY = Math.min(SIZE + 60, (HEIGHT - 140) / rows);
        let fromTwitch = 0;

        faces.forEach((face, i) => {
            const x = gapX * (i % COLUMNS) + gapX / 2;
            const y = 140 + gapY * Math.floor(i / COLUMNS) + SIZE / 2;
            const key = this.textures.exists(`face-${i}`) ? `face-${i}` : '__MISSING';

            const avatar = this.add.image(x, y, key).setDisplaySize(SIZE, SIZE);
            avatar.enableFilters().filters.internal.addMask('circle');

            this.add.text(x, y + SIZE / 2 + 8, face.name, {
                fontFamily: 'system-ui, sans-serif',
                fontSize: '20px',
                color: '#ffffff',
            }).setOrigin(0.5, 0);

            // A gentle bob so it looks alive.
            this.tweens.add({
                targets: avatar,
                y: y - 8,
                duration: 900 + (i % 7) * 120,
                yoyo: true,
                repeat: -1,
                ease: 'Sine.inOut',
            });

            if (key !== '__MISSING' && face.avatar.includes('jtvnw.net')) {
                fromTwitch++;
            }
        });

        const summary = `${faces.length} faces · ${fromTwitch} from Twitch CDN · ${faces.length - fromTwitch - this.failed.length} stock · ${this.failed.length} failed · renderer: ${this.game.renderer.type === Phaser.WEBGL ? 'WebGL' : 'Canvas'}`;
        this.add.text(WIDTH / 2, 50, summary, {
            fontFamily: 'system-ui, sans-serif',
            fontSize: '28px',
            color: this.failed.length ? '#f87171' : '#4ade80',
        }).setOrigin(0.5);

        log(summary);
    }
}

new Phaser.Game({
    type: Phaser.WEBGL,
    parent: 'game',
    width: WIDTH,
    height: HEIGHT,
    backgroundColor: '#18181b',
    scale: { mode: Phaser.Scale.FIT, autoCenter: Phaser.Scale.CENTER_BOTH },
    scene: FacesScene,
});
