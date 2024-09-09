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
