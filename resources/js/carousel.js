/*
 * This handles marking "active" the thumbnail of the currently displayed image
 * in the carousel
 */
document.addEventListener('DOMContentLoaded', () => {

    // Process all carousels on the page
    document.querySelectorAll('.carousel').forEach(el => {

        // Hook to the slide event
        el.addEventListener('slide.bs.carousel', e => {

            // Set the active class on the thumbnail the carousel is sliding to
            document.querySelectorAll(`.carousel-thumbnails a[href="#${el.id}"][data-slide-to="${e.to}"]`)
                .forEach(thumbnail => {
                    thumbnail.classList.toggle('active');
                });

            // Remove the active class on the thumbnail the carousel is sliding from
            document.querySelectorAll(`.carousel-thumbnails a[href="#${el.id}"][data-slide-to="${e.from}"]`)
                .forEach(thumbnail => {
                    thumbnail.classList.toggle('active');
                });
        });
    });
});
