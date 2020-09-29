const SimpleLightbox = require('simple-lightbox');

document.addEventListener('DOMContentLoaded', () => {
    // Find all elements with class .lightbox-gallery and build
    // a lightbox for each child link element (thumbnail)
    document.querySelectorAll('.lightbox-gallery').forEach(el => {
        new SimpleLightbox({
            elements: el.querySelectorAll('a.lightbox-link'),
        });
    });
});
