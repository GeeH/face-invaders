// Game art, loaded by key so any sprite can be swapped without touching game code.
// Kenney sprites are CC0 (see public/game/sprites/kenney/license.txt).
const HULLS = [1, 2, 3];
const COLOURS = ['blue', 'green', 'orange', 'red'];

// A random one of these is the player's ship each run.
export const PLAYER_SHIPS = HULLS.flatMap((hull) => COLOURS.map((colour) => `player-${hull}-${colour}`));

// Enemies are saucers with a follower's face in the porthole.
export const SAUCERS = ['Red', 'Green', 'Yellow', 'Blue'].map((colour) => `saucer-${colour.toLowerCase()}`);

export const SPRITES = {
    bullet: 'kenney/laserBlue01.png',
    hit: 'kenney/laserBlue08.png',
    ...Object.fromEntries(
        HULLS.flatMap((hull) => COLOURS.map((colour) => [`player-${hull}-${colour}`, `kenney/playerShip${hull}_${colour}.png`])),
    ),
    ...Object.fromEntries(
        ['Red', 'Green', 'Yellow', 'Blue'].map((colour) => [`saucer-${colour.toLowerCase()}`, `kenney/ufo${colour}.png`]),
    ),
};

export function preloadSprites(scene) {
    scene.load.setPath('/game/sprites/');
    Object.entries(SPRITES).forEach(([key, path]) => scene.load.image(key, path));
    scene.load.setPath('');
}
