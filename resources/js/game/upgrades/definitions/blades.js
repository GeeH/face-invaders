export default {
    id: 'blades',
    name: 'Orbiting blades',
    describe: () => '+1 blade circles your ship',
    apply: ({ arsenal }) => {
        arsenal.blades++;
    },
};
