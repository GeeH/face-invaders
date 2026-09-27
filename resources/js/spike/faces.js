// Spike for #10: can Phaser load Twitch avatars straight from Twitch's CDN and draw them with WebGL?
import Phaser from 'phaser';

const faces = JSON.parse(document.getElementById('faces').textContent);
const log = (...args) => console.log('[faces-spike]', ...args);
const loadAvatar = (loader, key, url) =>
    url.endsWith('.svg') ? loader.svg(key, url, { width: 256, height: 256 }) : loader.image(key, url);

const WIDTH = 1920;
const HEIGHT = 1080;
const SIZE = 150;
const COLUMNS = 10;

class FacesScene extends Phaser.Scene {
    constructor() {
        super('faces');
        this.failed = [];
        this.fellBack = 0;
        this.textureKeys = faces.map((_, i) => `face-${i}`);
    }

    preload() {
        // Twitch's CDN sends Access-Control-Allow-Origin: *, so anonymous CORS keeps WebGL textures untainted.
        this.load.setCORS('anonymous');

        faces.forEach((face, i) => loadAvatar(this.load, `face-${i}`, face.avatar));

        this.load.on('loaderror', (file) => this.failed.push(file));
    }

    create() {
        // Twitch sometimes hands out avatar URLs that 404: load the viewer's stock alien instead.
        // This has to be a second loader pass; adding files from inside 'loaderror' stalls the loader.
        const retry = this.failed.filter((file) => file.key.startsWith('face-'));
        this.failed = [];

        if (retry.length === 0) {
            this.build();
            return;
        }

        retry.forEach((file) => {
            const i = Number(file.key.replace('face-', ''));
            log('avatar failed, using stock fallback', file.src);
            this.textureKeys[i] = `fallback-${i}`;
            loadAvatar(this.load, `fallback-${i}`, faces[i].fallback);
        });

        this.fellBack = retry.length;
        this.load.once('complete', () => this.build());
        this.load.start();
    }

    build() {
        // A white circle shared by every avatar as its mask.
        this.add.graphics().fillStyle(0xffffff).fillCircle(128, 128, 128).generateTexture('circle', 256, 256).destroy();

        const rows = Math.ceil(faces.length / COLUMNS);
        const gapX = WIDTH / COLUMNS;
        const gapY = Math.min(SIZE + 60, (HEIGHT - 140) / rows);
        let fromTwitch = 0;

        faces.forEach((face, i) => {
            const x = gapX * (i % COLUMNS) + gapX / 2;
            const y = 140 + gapY * Math.floor(i / COLUMNS) + SIZE / 2;
            const key = this.textures.exists(this.textureKeys[i]) ? this.textureKeys[i] : '__MISSING';

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

            if (face.avatar.includes('jtvnw.net')) {
                fromTwitch++;
            }
        });

        const summary = `${faces.length} faces · ${fromTwitch - this.fellBack} from Twitch CDN · ${faces.length - fromTwitch} stock · ${this.fellBack} fell back to stock · ${this.failed.length} failed · renderer: ${this.game.renderer.type === Phaser.WEBGL ? 'WebGL' : 'Canvas'}`;
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
