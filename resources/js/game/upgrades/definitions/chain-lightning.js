export default {
    id: 'chain-lightning',
    name: 'Chain lightning',
    describe: () => 'Hits arc to +1 more rock',
    apply: ({ arsenal }) => {
        arsenal.chain++;
    },
};
