export default {
    id: 'piercing',
    name: 'Piercing rounds',
    describe: () => 'Bolts punch through +1 rock',
    apply: ({ arsenal }) => {
        arsenal.pierce++;
    },
};
