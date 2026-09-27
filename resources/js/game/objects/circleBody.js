/**
 * Give a physics sprite a centred circular hitbox. Arcade physics boxes never
 * rotate, so a circle is the only shape that stays accurate as sprites turn.
 */
export function useCircleBody(sprite, scale = 0.45) {
    const radius = Math.min(sprite.width, sprite.height) * scale;
    sprite.body.setCircle(radius, sprite.width / 2 - radius, sprite.height / 2 - radius);
}
