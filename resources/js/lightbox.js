const SimpleLightbox = require('simple-lightbox');

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.lightbox-gallery').forEach(el => {
        new SimpleLightbox({
            elements: el.querySelectorAll('a.lightbox-link'),
        });
    });
});
