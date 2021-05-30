const autoComplete = require('@tarekraafat/autocomplete.js/dist/js/autoComplete');

document.addEventListener('DOMContentLoaded', () => {
    /*
    * Setup autocomplete for inputs of class .autocomplete.
    *
    * The parent element is supposed to have is position set to relative for the
    * display to work properly
    */
    document.querySelectorAll('.autocomplete').forEach(el => {

        new autoComplete({
            data: {
                src: async function () {
                    // The input must have a data-autocomplete-endpoint attribute containing the URL to call
                    const source = await fetch(`${el.dataset.autocompleteEndpoint}?q=${encodeURIComponent(el.value)}`);
                    const data = await source.json();
                    return data;
                },
                // The input must have a data-autocomplete-key for the key to use in the result array of objects
                key: [el.dataset.autocompleteKey],
                cache: false
            },
            threshold: 1,
            debounce: 300,
            maxResults: 10,
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
            }
        });
    });
});
