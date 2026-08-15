import Chart from 'chart.js';

/**
 * Draw every chart on the page.
 *
 * The config is built in PHP and handed over on the canvas itself, rather than
 * each view emitting an inline `new Chart(...)` against a global this module
 * used to publish. An inline script cannot see whether the bundle arrived, so a
 * chart that never drew looked exactly like a page with no data (#272).
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-chart-config]').forEach((canvas) => {
        new Chart(canvas, JSON.parse(canvas.dataset.chartConfig));
    });
});
