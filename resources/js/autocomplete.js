import autoComplete from '@tarekraafat/autocomplete.js/dist/js/autoComplete';

document.addEventListener('DOMContentLoaded', () => {
    // Keep track of all the auto-complete instances
    // This is needed for the magazine index editor as we
    // re-trigger the installation of auto-complete and must
    // first uninitialize all the existing ones
    var autoCompletes = [];

    // Focus listeners waiting to lazily build an instance (see below). Kept so
    // they can be detached when autocomplete is re-registered.
    var pendingFocus = [];

    /*
    * Build an autoComplete instance for a single input of class .autocomplete.
    *
    * The parent element is supposed to have its position set to relative for the
    * display to work properly.
    */
    function build(el) {
        var ac = new autoComplete({
            data: {
                src: async function () {
                    // The input must have a data-autocomplete-endpoint attribute containing the URL to call
                    try {
                        const source = await fetch(`${el.dataset.autocompleteEndpoint}`
                                + `?q=${encodeURIComponent(el.value)}`);
                        return await source.json();
                    } catch (e) {
                        // The fetch can be aborted (e.g. the page navigates away on
                        // redirect). Degrade gracefully instead of throwing an
                        // uncaught promise rejection.
                        return [];
                    }
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
    }

    // Register a custom event listener which allows us to re-trigger the
    // registering of auto-complete. This is needed for Livewire components as we
    // add elements to the DOM and need to have the auto-complete applied to them.
    //
    // Instances are built lazily on first focus rather than eagerly for every
    // matching input: the dropdown is only useful once a field is focused, and
    // on screens with hundreds of inputs (e.g. the menu bulk-import review)
    // building them all up front added avoidable work to an already heavy
    // post-render pass.
    document.addEventListener('atarilegend:autocomplete', () => {
        // Tear down everything from the previous registration: dispose built
        // instances and remove not-yet-fired focus listeners. (autoCompletes is
        // reset too, which the previous implementation neglected — it grew
        // unbounded across re-registrations.)
        autoCompletes.forEach(ac => ac.unInit());
        autoCompletes = [];
        pendingFocus.forEach(({el, handler}) => el.removeEventListener('focus', handler));
        pendingFocus = [];

        document.querySelectorAll('.autocomplete').forEach(el => {
            // A field that is focused already will never fire the focus event
            // we would be waiting for, and would stay inert until it is blurred
            // and focused again. That happens both to someone who clicks into
            // the field before the scripts have run, and to a field a Livewire
            // re-render hands back still focused, so build it now instead.
            if (document.activeElement === el) {
                build(el);
                return;
            }

            const handler = () => {
                el.removeEventListener('focus', handler);
                pendingFocus = pendingFocus.filter(p => p.el !== el);
                // The input is already focused; the instance's own listeners
                // pick up the user's keystrokes from here.
                build(el);
            };
            el.addEventListener('focus', handler);
            pendingFocus.push({el, handler});
        });
    });

    // Trigger initial auto-complete setup event
    window.document.dispatchEvent(new Event('atarilegend:autocomplete', {
        bubbles: true,
        cancelable: true,
    }));
});
