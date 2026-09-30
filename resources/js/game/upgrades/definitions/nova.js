export default {
    id: 'nova',
    name: 'Nova pulse',
    describe: () => 'Blasts a shockwave, often',
    apply: ({ arsenal }) => {
        arsenal.nova++;
    },
};
