export default {
    id: 'max-health',
    name: 'Max Health',
    describe: () => 'One extra life',
    apply: ({ state }) => {
        state.addMaxHealth(1);
    },
};
