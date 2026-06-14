import * as bootstrap from 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {

    // Handle the creation or use of an existing release, when creating menu content
    var createReleaseEl = document.getElementById('action-create-release');
    var useReleaseEl = document.getElementById('action-use-release');

    if (createReleaseEl && useReleaseEl) {
        var createRelease = new bootstrap.Collapse(createReleaseEl, {'toggle': false});
        var useRelease = new bootstrap.Collapse(useReleaseEl, {'toggle': false});

        document.getElementById('create-release').addEventListener('change', () => {
            createRelease.toggle();
            useRelease.toggle();
        });

        document.getElementById('use-release').addEventListener('change', () => {
            createRelease.toggle();
            useRelease.toggle();
        });
    }

});

// Clear the visible text of an autocomplete input when its "×" button is
// clicked. The companion server state is cleared via the button's wire:click;
// this only resets the on-screen value, which Livewire won't do on its own
// because the autocomplete library set it programmatically (making it "dirty").
document.addEventListener('click', (e) => {
    const button = e.target.closest('.js-clear-autocomplete');
    if (!button) {
        return;
    }

    const input = button.closest('.input-group')?.querySelector('.autocomplete');
    if (input) {
        input.value = '';
    }
});

// Forward a free-typed game / software / donator name on the MenuImport review
// screen to the matching Livewire method. The visible autocomplete inputs used
// to carry an Alpine `x-on:change` directive each; with up to ~200 rows,
// compiling that many *unique* expressions (one per row index) was a large part
// of the post-upload main-thread freeze. A single delegated listener does the
// same job with zero per-row setup. The target method + its leading arguments
// are declared on the input via data-search-method / data-search-args; the
// typed value is appended as the final argument.
//
// Selecting a suggestion is handled separately by the hidden companion input's
// wire:change (it carries the resolved id); the server methods short-circuit
// when the value already matches, so the change that also fires here on blur is
// harmless.
document.addEventListener('change', (e) => {
    const input = e.target.closest('input[data-search-method]');
    if (!input || !window.Livewire) {
        return;
    }

    const root = input.closest('[wire\\:id]');
    if (!root) {
        return;
    }

    const wire = window.Livewire.find(root.getAttribute('wire:id'));
    if (!wire) {
        return;
    }

    const args = JSON.parse(input.dataset.searchArgs || '[]');
    wire.call(input.dataset.searchMethod, ...args, input.value);
});

// Re-attach the autocomplete controls to the game / software inputs of the
// MenuImport review screen. Those inputs are added to the DOM only after the
// Excel file is uploaded, so we must (re-)trigger the autocomplete setup once
// Livewire has rendered them.
document.addEventListener('livewire:initialized', () => {
    Livewire.on('menu-import-rendered', () => {
        setTimeout(() => {
            window.document.dispatchEvent(
                new CustomEvent('atarilegend:autocomplete', {
                    bubbles: true,
                    cancelable: true,
                })
            );
        }, 50);
    });

    // When an import is attempted with unresolved problems, scroll the first
    // flagged field into view so the user can find what to fix.
    Livewire.on('menu-import-has-errors', () => {
        setTimeout(() => {
            const target = document.querySelector('.is-invalid, .table-danger');
            if (target) {
                target.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        }, 50);
    });
});
