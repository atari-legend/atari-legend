import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import checker from 'vite-plugin-checker'
import * as path from 'path';

// FontAwesome's compiled CSS hardcodes font-display: block on its @font-face
// rules, which still hides icon glyphs for up to 3s on a slow connection.
// Importing its SCSS source instead (to override $fa-font-display) breaks
// Vite's asset-URL rewriting for the webfont files, so this rewrites the
// compiled output directly instead - a plain string swap, so it isn't tied
// to FontAwesome's current file names and survives a version bump untouched.
function fontAwesomeSwapFontDisplay() {
    return {
        name: 'fontawesome-swap-font-display',
        transform(code) {
            if (!code.includes('font-display')) {
                return;
            }

            return code.replace(/font-display:\s*block/g, 'font-display: swap');
        },
    };
}

/** @type {import('vite').UserConfig} */
export default defineConfig({
    plugins: [
        fontAwesomeSwapFontDisplay(),
        checker({
            eslint: {
                lintCommand: 'eslint "resources/js/**/*.js"',
                useFlatConfig: true,
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
