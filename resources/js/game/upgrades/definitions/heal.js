export default {
    id: 'heal',
    name: 'Heal',
    describe: (balance) => `Repair ${balance.heal_upgrade} lives`,
    apply: ({ state, balance }) => {
        state.heal(balance.heal_upgrade);
    },
};
