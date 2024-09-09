import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import checker from 'vite-plugin-checker'
import * as path from 'path';

/** @type {import('vite').UserConfig} */
export default defineConfig({
    plugins: [
        checker({
            eslint: {
                lintCommand: 'eslint -c resources/js/eslint.config.mjs "resources/js/**/*.js"'
            }
        }),
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/sass/admin/admin.scss',
                'resources/js/admin/app.js',
                'resources/js/game/music.js',
                'resources/js/tabulator.js',
                'resources/js/charts.js',
                'resources/js/menus.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: true,
        origin: 'http://localhost:5173'
    }
});
