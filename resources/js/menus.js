const Isotope = require('isotope-layout');

document.addEventListener('DOMContentLoaded', () => {
    var isotope = new Isotope('.isotope', {});

    document.querySelectorAll('[data-isotope-filter]').forEach(el => {
        el.addEventListener('click', (e) => {
            isotope.arrange({filter: el.dataset.isotopeFilter});
            e.preventDefault();
        });
    });
});
