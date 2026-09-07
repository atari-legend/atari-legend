/**
 * Lite YouTube embed: swaps the static thumbnail for a live iframe only once
 * the visitor actually asks to play it, instead of loading the YouTube
 * player unconditionally on every game page.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.video-facade').forEach(el => {
        function play() {
            const iframe = document.createElement('iframe');
            iframe.src = `https://www.youtube-nocookie.com/embed/${el.dataset.youtubeId}?autoplay=1`;
            iframe.title = el.dataset.youtubeTitle;
            iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;

            el.replaceChildren(iframe);
            el.removeAttribute('role');
            el.removeAttribute('tabindex');
            el.removeAttribute('aria-label');
        }

        el.addEventListener('click', play);
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                play();
            }
        });
    });
});
