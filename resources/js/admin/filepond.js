const filepond = require('filepond');
const filePondPluginImagePreview = require('filepond-plugin-image-preview');
const filePondPluginFileValidateType = require('filepond-plugin-file-validate-type');

document.addEventListener('DOMContentLoaded', () => {
    filepond.registerPlugin(filePondPluginImagePreview);
    filepond.registerPlugin(filePondPluginFileValidateType);

    document.querySelectorAll('input.filepond').forEach(el => {
        var filetypes = el.dataset.filepondFiletypes || '';
        filepond.create(el, {
            'acceptedFileTypes': filetypes.split(','),
            'chunkUploads': true,
            'server': {
                url: '/admin/filepond/api',
                process: '/process',
                revert: '/process',
                patch: '?patch=',
            }
        });
    });
});
