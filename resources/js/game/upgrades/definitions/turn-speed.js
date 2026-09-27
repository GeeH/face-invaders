const percent = (fraction) => Math.round(fraction * 100);

export default {
    id: 'turn-speed',
    name: 'Turn speed',
    describe: (balance) => `Turn ${percent(balance.turn_speed_upgrade)}% faster`,
    apply: ({ player, balance }) => {
        player.turnSpeed *= 1 + balance.turn_speed_upgrade;
    },
};
