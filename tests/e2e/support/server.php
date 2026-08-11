<?php

/**
 * Router script for the PHP dev server the Playwright suite runs against.
 *
 * `php artisan serve` would do the same job, but it also parses the dev
 * server's log lines to pretty-print them, and that parser races under
 * concurrency: with several Playwright workers in flight it dies partway
 * through a run on 'Undefined array key 0', taking every remaining test with
 * it. Skipping the wrapper skips the parser.
 *
 * Laravel ships an equivalent router, but it resolves the public directory
 * from getcwd(), which ties the command to the directory it is launched from.
 * Ten lines here are cheaper than that coupling, and than depending on a path
 * inside vendor/ that a framework upgrade may move.
 */
$publicPath = dirname(__DIR__, 3) . '/public';

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Emulate mod_rewrite: serve a file that exists, hand everything else to
// Laravel. Returning false tells the dev server to serve the file itself.
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    return false;
}

require_once $publicPath . '/index.php';
