// Placeholder scoring, to tune alongside balance: later waves are worth more.
export const POINTS_PER_KILL = 10;
export const WAVE_CLEAR_BONUS = 100;

/**
 * Everything about the current run: health, score and the stats saved when
 * it ends (#28). Plain JavaScript so it can be tested without Phaser.
 */
export class RunState {
    constructor({ startingHealth, now = Date.now() }) {
        this.health = startingHealth;
        this.maxHealth = startingHealth;
        this.score = 0;
        this.kills = 0;
        this.wave = 0;
        this.upgrades = [];
        this.startedAt = now;
        this.endedAt = null;
    }

    get dead() {
        return this.health <= 0;
    }

    startWave(wave) {
        this.wave = wave;
    }

    /**
     * An enemy was shot down.
     */
    recordKill() {
        this.kills++;
        this.score += POINTS_PER_KILL * Math.max(1, this.wave);
    }

    waveCleared() {
        this.score += WAVE_CLEAR_BONUS * this.wave;
    }

    /**
     * Lose health; returns true if this was the fatal blow.
     */
    takeDamage(amount = 1) {
        if (this.dead) {
            return false;
        }

        this.health = Math.max(0, this.health - amount);

        return this.dead;
    }

    heal(amount) {
        this.health = Math.min(this.maxHealth, this.health + amount);
    }

    addMaxHealth(amount) {
        this.maxHealth += amount;
        this.health += amount;
    }

    recordUpgrade(id) {
        this.upgrades.push(id);
    }

    end(now = Date.now()) {
        this.endedAt ??= now;
    }

    /**
     * The stats sent to Laravel when the run ends (#28).
     */
    stats(now = Date.now()) {
        return {
            score: this.score,
            wave_reached: this.wave,
            kills: this.kills,
            duration_seconds: Math.round(((this.endedAt ?? now) - this.startedAt) / 1000),
            upgrades: [...this.upgrades],
        };
    }
}
