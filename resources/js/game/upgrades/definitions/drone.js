export default {
    id: 'drone',
    name: 'Drone wingman',
    describe: () => '+1 drone fires for you',
    apply: ({ arsenal }) => {
        arsenal.drones++;
    },
};
