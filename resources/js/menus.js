import  Isotope from 'isotope-layout';

// app.scss (Bootstrap's grid rules included) loads asynchronously via a
// preload/swap in the main layout, so it may not be applied yet by
// DOMContentLoaded. Isotope measures box sizes at init time to compute its
// layout; if it runs before the stylesheet is applied, it packs rows using
// unstyled (shorter) box heights, and the resulting layout overlaps once the
// real styles land. Wait for the stylesheet to actually be applied first.
function stylesReady() {
    const link = document.getElementById('app-styles');
    if (!link || link.rel === 'stylesheet') {
        return Promise.resolve();
    }
    return new Promise(resolve => link.addEventListener('load', resolve, {once: true}));
}

document.addEventListener('DOMContentLoaded', () => {
    stylesReady().then(() => {
        var isotope = new Isotope('.isotope', {});

        document.querySelectorAll('[data-isotope-filter]').forEach(el => {
            el.addEventListener('click', (e) => {
                document.querySelectorAll('[data-isotope-filter]').forEach(e => {
                    e.parentElement.classList.remove('active');
                });
                el.parentElement.classList.add('active');

                isotope.arrange({filter: el.dataset.isotopeFilter});
                e.preventDefault();
            });
        });
    });
});
