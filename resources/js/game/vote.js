/**
 * One vote between waves: chat (or the streamer) picks one of the options.
 *
 * Viewers each get one vote; voting again replaces it. The streamer can pick
 * an option outright, which ends the vote immediately. When time runs out,
 * the option with most votes wins; a tie is a random pick among the leaders,
 * and no votes at all is a random pick from every option.
 *
 * Plain JavaScript so it can be tested without Phaser. #24 feeds chat votes
 * into castVote(); the local stub (#18) uses pick() from the keyboard.
 */
export class Vote {
    constructor(options, durationMs, random = Math.random) {
        this.options = options;
        this.remainingMs = durationMs;
        this.random = random;
        this.ballots = new Map();
        this.winner = null;
    }

    get finished() {
        return this.winner !== null;
    }

    /**
     * A viewer votes for option number `choice` (1-based, as typed in chat).
     */
    castVote(voter, choice) {
        if (this.finished || !Number.isInteger(choice) || choice < 1 || choice > this.options.length) {
            return false;
        }

        this.ballots.set(voter, choice - 1);

        return true;
    }

    /**
     * Votes per option, in option order.
     */
    tally() {
        const counts = this.options.map(() => 0);
        this.ballots.forEach((index) => counts[index]++);

        return counts;
    }

    /**
     * The streamer picks option `choice` (1-based), ending the vote now.
     */
    pick(choice) {
        if (this.finished || choice < 1 || choice > this.options.length) {
            return null;
        }

        this.winner = this.options[choice - 1];

        return this.winner;
    }

    /**
     * Count down; returns the winner once the vote has finished.
     */
    update(delta) {
        if (!this.finished) {
            this.remainingMs = Math.max(0, this.remainingMs - delta);

            if (this.remainingMs === 0) {
                this.winner = this.decide();
            }
        }

        return this.winner;
    }

    decide() {
        const counts = this.tally();
        const most = Math.max(...counts);
        const leaders = this.options.filter((_, i) => counts[i] === most);

        // With no votes every option is a "leader", so this also covers that case.
        return leaders[Math.floor(this.random() * leaders.length)];
    }
}
