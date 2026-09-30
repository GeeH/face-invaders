export default {
    id: 'explosive',
    name: 'Explosive rounds',
    describe: () => 'Kills explode in a chain',
    apply: ({ arsenal }) => {
        arsenal.explosive++;
    },
};
