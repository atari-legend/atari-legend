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
                document.querySelector(`#${id}`).classList.toggle('d-none');
            });
            e.preventDefault();
        });
    });
});
