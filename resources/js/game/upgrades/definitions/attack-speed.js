const percent = (fraction) => Math.round(fraction * 100);

export default {
    id: 'attack-speed',
    name: 'Attack speed',
    describe: (balance) => `Fire ${percent(balance.attack_speed_upgrade)}% faster`,
    apply: ({ player, balance }) => {
        player.fireRate *= 1 + balance.attack_speed_upgrade;
    },
};
