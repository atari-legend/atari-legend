/*
 * Handles resizing of the thumbnails column for the game screenshots
 *
 * The thumbnail column should take the same height as the main screenshot
 *
 */
document.addEventListener('DOMContentLoaded', () => {

    /**
     * Sets the thumbnails column height, depending on the main
     * screenshot height
     */
    function setThumbnailsHeight() {
        var el = document.getElementById('carousel-screenshots');
        document.querySelector('[data-bs-carousel="carousel-screenshots"]').style.height = `${el.offsetHeight}px`;
    }

    var el = document.getElementById('carousel-screenshots');
    if (el) {
        window.addEventListener('resize', setThumbnailsHeight);
        // Set initial size
        setTimeout(setThumbnailsHeight, 250);
    }
});
