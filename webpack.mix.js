const mix = require('laravel-mix');

require('laravel-mix-eslint');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .eslint()
    .sass('resources/sass/app.scss', 'public/css')
    .sass('resources/sass/admin/admin.scss', 'public/css')
    .version()
    .sourceMaps();

mix.js('resources/js/admin/app.js', 'public/js/admin.js')
    .eslint()
    .version()
    .sourceMaps();

mix.js('resources/js/game/music.js', 'public/js/game-music.js')
    .eslint()
    .version()
    .sourceMaps();

mix.js('resources/js/tabulator.js', 'public/js')
    .eslint()
    .version()
    .sourceMaps();

mix.js('resources/js/charts.js', 'public/js')
    .eslint()
    .version()
    .sourceMaps();

mix.js('resources/js/menus.js', 'public/js')
    .eslint()
    .version()
    .sourceMaps();
