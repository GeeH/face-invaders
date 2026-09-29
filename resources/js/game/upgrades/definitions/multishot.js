export default {
    id: 'multishot',
    name: 'Multishot',
    describe: () => '+1 bolt per shot, fanned out',
    apply: ({ arsenal }) => {
        arsenal.shots++;
    },
};
