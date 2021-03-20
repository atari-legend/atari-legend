const bootstrap = require('bootstrap');

document.addEventListener('DOMContentLoaded', () => {

    // Handle the creation or use of an existing release, when creating menu content
    var createRelease = new bootstrap.Collapse(document.getElementById('action-create-release'), {'toggle': false});
    var useRelease = new bootstrap.Collapse(document.getElementById('action-use-release'), {'toggle': false});

    document.getElementById('create-release').addEventListener('change', () => {
        createRelease.toggle();
        useRelease.toggle();
    });

    document.getElementById('use-release').addEventListener('change', () => {
        createRelease.toggle();
        useRelease.toggle();
    });

});
