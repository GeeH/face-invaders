// Size of the baked round face textures, in pixels.
const FACE_SIZE = 128;

/**
 * The texture key for a face once it has been loaded and cropped to a circle.
 */
export const faceKey = (face) => `face:${face.id}`;

/**
 * Load every face's avatar and bake it into a round texture, ready to drop
 * into an enemy's porthole. Faces already loaded by an earlier run are reused.
 *
 * Twitch occasionally serves avatar URLs that 404, so any that fail are
 * loaded again from the face's stock fallback in a second pass (queuing
 * files from inside 'loaderror' stalls Phaser's loader).
 */
export async function loadFaces(scene, faces) {
    const missing = faces.filter((face) => !scene.textures.exists(faceKey(face)));

    const failed = await loadAvatars(scene, missing, (face) => face.avatar);
    await loadAvatars(scene, failed, (face) => face.fallback);

    missing.forEach((face) => bakeRound(scene, face));
}

function loadAvatars(scene, faces, urlFor) {
    if (faces.length === 0) {
        return Promise.resolve([]);
    }

    return new Promise((resolve) => {
        const failed = [];
        const byKey = new Map(faces.map((face) => [rawKey(face), face]));

        scene.load.setCORS('anonymous');
        faces.forEach((face) => {
            const url = urlFor(face);

            if (url.endsWith('.svg')) {
                scene.load.svg(rawKey(face), url, { width: FACE_SIZE, height: FACE_SIZE });
            } else {
                scene.load.image(rawKey(face), url);
            }
        });

        const onError = (file) => byKey.has(file.key) && failed.push(byKey.get(file.key));
        scene.load.on('loaderror', onError);
        scene.load.once('complete', () => {
            scene.load.off('loaderror', onError);
            resolve(failed);
        });
        scene.load.start();
    });
}

/**
 * Crop a loaded avatar to a circle once, rather than masking every enemy every frame.
 */
function bakeRound(scene, face) {
    if (!scene.textures.exists(rawKey(face))) {
        return; // Even the fallback failed; the enemy shows an empty porthole.
    }

    const source = scene.textures.get(rawKey(face)).getSourceImage();
    const texture = scene.textures.createCanvas(faceKey(face), FACE_SIZE, FACE_SIZE);
    const context = texture.getContext();

    context.beginPath();
    context.arc(FACE_SIZE / 2, FACE_SIZE / 2, FACE_SIZE / 2, 0, Math.PI * 2);
    context.clip();
    context.drawImage(source, 0, 0, FACE_SIZE, FACE_SIZE);
    texture.refresh();

    scene.textures.remove(rawKey(face));
}

const rawKey = (face) => `avatar:${face.id}`;

/**
 * Hands out faces in turn, going round again when it runs out.
 */
export class FaceQueue {
    constructor(faces) {
        this.faces = faces;
        this.index = 0;
    }

    next() {
        if (this.faces.length === 0) {
            return null;
        }

        return this.faces[this.index++ % this.faces.length];
    }

    /**
     * The next few faces to be handed out, without taking them.
     */
    peek(count) {
        if (this.faces.length === 0) {
            return [];
        }

        return Array.from({ length: count }, (_, i) => this.faces[(this.index + i) % this.faces.length]);
    }
}
