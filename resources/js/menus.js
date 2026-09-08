import  Isotope from 'isotope-layout';

document.addEventListener('DOMContentLoaded', () => {
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
