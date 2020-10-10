/*
 * This handles marking "active" the thumbnail of the currently displayed image
 * in the carousel
 */
document.addEventListener('DOMContentLoaded', () => {

    // Process all carousels on the page
    document.querySelectorAll('.carousel').forEach(el => {

        // Hook to the slide event
        el.addEventListener('slide.bs.carousel', e => {

            // For the thumbnail the carousel is sliding to...
            document.querySelectorAll(`.carousel-thumbnails a[href="#${el.id}"][data-slide-to="${e.to}"]`)
                .forEach(thumbnail => {
                    // ...set the active class on
                    thumbnail.classList.toggle('active');

                    // ...scroll the parent to make the thumbnail visible, either
                    // on the X or Y axis depending if the thumbnails are horizontal
                    // or vertical
                    if (el.classList.contains('carousel-thumbnails-vertical')) {
                        document.querySelector(`.carousel-thumbnails[data-carousel="${el.id}"]`)
                            .scrollTo(0, thumbnail.offsetTop - 250);
                    } else if (el.classList.contains('carousel-thumbnails-horizontal')) {
                        document.querySelector(`.carousel-thumbnails[data-carousel="${el.id}"]`)
                            .scrollTo(thumbnail.offsetLeft - 150, 0);
                    }

                });

            // Remove the active class on the thumbnail the carousel is sliding from
            document.querySelectorAll(`.carousel-thumbnails a[href="#${el.id}"][data-slide-to="${e.from}"]`)
                .forEach(thumbnail => {
                    thumbnail.classList.toggle('active');
                });
        });
    });

    // Always reset the scroll on the thumbnails on load. Browsers may preserve the scroll
    // across reload rather than scroll back to zero
    document.querySelectorAll('.carousel-thumbnails').forEach(el => el.scrollTo(0, 0));
});
