const filepond = require('filepond');
const filePondPluginImagePreview = require('filepond-plugin-image-preview');
const filePondPluginFileValidateType = require('filepond-plugin-file-validate-type');

/**
 * Setup Filepond drag'n drop file upload
 */
document.addEventListener('DOMContentLoaded', () => {
    filepond.registerPlugin(filePondPluginImagePreview);
    filepond.registerPlugin(filePondPluginFileValidateType);

    document.querySelectorAll('input.filepond').forEach(el => {

        var opts = {
            'chunkUploads': true,
            'server': {
                url: '/admin/filepond/api',
                process: '/process',
                revert: '/process',
                patch: '?patch=',
            }
        };

        var filetypes = el.dataset.filepondFiletypes;
        if (filetypes) {
            opts.acceptedFileTypes = filetypes.split(',');
        }

        var pond = filepond.create(el, opts);

        // If a submit button selector was set in data attributes,
        // enable it when all files have been processed (the first time)
        var buttonSelector = el.dataset.filepondButton;
        if (buttonSelector) {
            var button = document.querySelector(buttonSelector);
            if (button) {
                pond.on('processfiles', () => {
                    button.disabled = false;
                });

            }
        }
    });
});
