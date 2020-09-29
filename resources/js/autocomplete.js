const autoComplete = require('@tarekraafat/autocomplete.js/dist/js/autoComplete');

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
                const source = await fetch(`${el.dataset.autocompleteEndpoint}?q=${el.value}`);
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
        selector: () => el,
        resultsList: {
            render: true,
            container: source => {
                source.classList.add(
                    'autocomplete-results',
                    'list-unstyled',
                    'text-audiowide');
            },
            destination: el,
        },
        onSelection: feedback => {
            el.value = feedback.selection.value[el.dataset.autocompleteKey];
            // Prevent default event otherwise it would submit the form
            feedback.event.preventDefault();
        }
    });
});
