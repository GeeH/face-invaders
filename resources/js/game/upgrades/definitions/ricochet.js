export default {
    id: 'ricochet',
    name: 'Ricochet',
    describe: () => 'Bolts bounce +1 more time',
    apply: ({ arsenal }) => {
        arsenal.bounces++;
    },
};
