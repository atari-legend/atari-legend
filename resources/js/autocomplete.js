import autoComplete from '@tarekraafat/autocomplete.js/dist/js/autoComplete';

document.addEventListener('DOMContentLoaded', () => {
    // Keep track of all the auto-complete instances
    // This is needed for the magazine index editor as we
    // re-trigger the installation of auto-complete and must
    // first uninitialize all the existing ones
    var autoCompletes = [];

    // Register a custom event listener which allows us to
    // re-trigger the registering of auto-complete. This is needed for
    // Livewire components as we add elements to the DOM and need to
    // have the auto-complete applied to them
    // There's probably a better way to do this...
    document.addEventListener('atarilegend:autocomplete', () => {
        // Uninitialize all existing instances
        autoCompletes.forEach(ac => {
            ac.unInit();
        });

        /*
        * Setup autocomplete for inputs of class .autocomplete.
        *
        * The parent element is supposed to have is position set to relative for the
        * display to work properly
        */
        document.querySelectorAll('.autocomplete').forEach(el => {
            var ac = new autoComplete({
                data: {
                    src: async function () {
                        // The input must have a data-autocomplete-endpoint attribute containing the URL to call
                        const source = await fetch(`${el.dataset.autocompleteEndpoint}`
                            + `?q=${encodeURIComponent(el.value)}`);
                        const data = await source.json();
                        return data;
                    },
                    // The input must have a data-autocomplete-key for the key to use in the result array of objects
                    key: [el.dataset.autocompleteKey],
                    cache: false
                },
                threshold: 1,
                debounce: 300,
                maxResults: el.dataset.autocompleteMax || 10,
                highlight: {
                    render: true
                },
                selector: () => el,
                resultsList: {
                    render: true,
                    container: source => {
                        source.classList.add(
                            'autocomplete-results',
                            'list-unstyled',
                            'text-audiowide');
                    },
                    destination: () => el,
                },
                resultItem: {
                    content: (data, element) => {
                        if (data.value.icon) {
                            var icon = document.createElement('span');
                            icon.classList.add(
                                'fa',
                                'fa-fw',
                                data.value.icon,
                                'text-muted',
                                'me-2');

                            element.insertBefore(icon, element.firstChild);
                        }
                    },
                },
                onSelection: feedback => {
                    if (feedback.selection.value.url && el.dataset.autocompleteFollowUrl === 'true') {
                        location.href = feedback.selection.value.url;
                        return;
                    }

                    el.value = feedback.selection.value[el.dataset.autocompleteKey];

                    if (el.dataset.autocompleteCompanion && el.dataset.autocompleteId) {
                        document.querySelectorAll(`input[name="${el.dataset.autocompleteCompanion}"]`)
                            .forEach(companion => {
                                companion.value = feedback.selection.value[el.dataset.autocompleteId];
                            });
                    }

                    if (el.dataset.autocompleteSubmit && el.dataset.autocompleteSubmit === 'true') {
                        el.form.submit();
                    }

                    // Force an change event in the companion hidden field
                    // This is required when binding the hidden field to a Livewire property to
                    // make sure the property is updated
                    document.querySelectorAll(`input[name="${el.dataset.autocompleteCompanion}"]`)
                        .forEach(companion => {
                            companion.dispatchEvent(new Event('change'));
                        });
                }
            });

            autoCompletes.push(ac);
        });
    });

    // Trigger initial auto-complete setup event
    window.document.dispatchEvent(new Event('atarilegend:autocomplete', {
        bubbles: true,
        cancelable: true,
    }));
});
