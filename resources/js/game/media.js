/**
 * Handles copying data into the clipboard. Is used for example to copy the
 * SHA512 sum of a dump
 */
document.addEventListener('DOMContentLoaded', () => {
    var bootstrap = require('bootstrap');

    document.querySelectorAll('[data-copy-text]').forEach(el => {
        el.addEventListener('click', async(e) => {
            try {
                await navigator.clipboard.writeText(el.dataset.copyText);
                var tooltip = new bootstrap.Tooltip(el, {
                    title: 'Copied to clipboard',
                });
                el.addEventListener('hidden.bs.tooltip', () => {
                    tooltip.disable();
                });
                tooltip.show();

            } catch (err) {
                console.error('Failed to copy to clipboard', err);
            }

            e.preventDefault();
        });
    });
});
