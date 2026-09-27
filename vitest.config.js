import { defineConfig } from 'vitest/config';

// Separate from vite.config.js: the Laravel Vite plugin isn't needed for unit tests and refuses to run in CI.
export default defineConfig({
    test: {
        include: ['resources/js/**/*.test.js'],
    },
});
