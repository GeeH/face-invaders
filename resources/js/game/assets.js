// Game art, loaded by key so any sprite can be swapped without touching game code.
// Kenney sprites are CC0 (see public/game/sprites/kenney/license.txt).
export const SPRITES = {
    base: 'kenney/ufoBlue.png',
    turret: 'kenney/gun04.png',
    bullet: 'kenney/laserBlue01.png',
    hit: 'kenney/laserBlue08.png',
    // Stand-in until the face-carrying enemy sprite (#14).
    enemy: 'kenney/enemyRed1.png',
};

export function preloadSprites(scene) {
    scene.load.setPath('/game/sprites/');
    Object.entries(SPRITES).forEach(([key, path]) => scene.load.image(key, path));
    scene.load.setPath('');
}
