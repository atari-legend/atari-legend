/**
 * Handle animations for the about page
 */
document.addEventListener('DOMContentLoaded', () => {
    const OFFSET = 150;

    // Start by hiding all blocks outside of the viewport
    document.querySelectorAll('.cd-timeline-block').forEach(el => {
        if (el.getBoundingClientRect().top > window.innerHeight) {
            el.classList.add('block-invisible');
        }
    });

    /**
     * Show blocks when they enter the viewport
     */
    function showBlocks() {
        // Find all invisible blocks
        document.querySelectorAll('.cd-timeline-block.block-invisible').forEach(el => {
            // Check if they have entered the viewport for some time
            if (el.getBoundingClientRect().top < window.innerHeight - OFFSET) {
                // Show them
                el.classList.remove('block-invisible');

                // Apply CSS animation
                el.querySelectorAll('.timeline-content').forEach(content => {
                    content.classList.add('bounce-in');
                });
                el.querySelectorAll('.cd-timeline-img').forEach(content => {
                    content.classList.add('bounce-in');
                });
            }
        });

    }

    window.addEventListener('scroll', () => {
        (!window.requestAnimationFrame)
            ? setTimeout(showBlocks, 100)
            : window.requestAnimationFrame(showBlocks);
    });
});
