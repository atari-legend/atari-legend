/**
 * Handles toggling between a dropdown and an input for
 * some of the search fields like Publisher, Developer, ...
 */
document.addEventListener('DOMContentLoaded', () => {

    // data-dropdown-toggle is supposed to contains the ID of the input
    // and dropdown to toggle, comma-delimited
    document.querySelectorAll('[data-dropdown-toggle]').forEach(el => {
        el.addEventListener('click', e => {
            el.dataset.dropdownToggle.split(',').forEach(id => {
                const field = document.querySelector(`#${id}`);
                field.classList.toggle('d-none');
                // A hidden field is still submitted, and a refined search
                // comes back with one of the pair filled: clear the one being
                // hidden so it doesn't silently narrow the search
                if (field.classList.contains('d-none')) {
                    field.value = '';
                    field.dispatchEvent(new Event('input'));
                }
            });
            e.preventDefault();
        });
    });

    // The individual is searched on the ID of the suggestion picked, held in
    // a hidden companion. Typing over the name means that ID no longer
    // matches what is shown
    const individual = document.querySelector('#individual');
    if (individual) {
        individual.addEventListener('input', () => {
            document.querySelectorAll(`input[name="${individual.dataset.autocompleteCompanion}"]`)
                .forEach(companion => companion.value = '');
        });
    }
});
