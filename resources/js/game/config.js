// The game always runs at 1920×1080 and is scaled to fit the browser source,
// so positions, speeds and balance behave the same whatever size OBS uses.
export const WIDTH = 1920;
export const HEIGHT = 1080;

// Pixels per second.
export const BULLET_SPEED = 1100;

// Radians per millisecond the turret can swivel.
export const TURRET_TURN_SPEED = 0.012;

// Enemies closer than this to the centre have reached the base.
export const BASE_RADIUS = 70;
