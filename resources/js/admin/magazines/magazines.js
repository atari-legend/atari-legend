const REGEX_ARCHIVE_ORG = new RegExp('https://archive.org/details/([^/]+)/$');

/**
 * Handle of magazine issue cover, either via stanard file upload or
 * by fetching it from archive.org
 */
document.addEventListener('DOMContentLoaded', () => {
    const coverElement = document.getElementById('issue-cover');
    const fileUpload = document.getElementById('image');
    const fetchButton = document.getElementById('fetch-thumbnail');
    const useArchiveOrgCoverInput = document.getElementById('useArchiveOrgCover');
    const destroyImageInput = document.getElementById('destroyImage');

    if (fetchButton) {
        const oldButtonHTML = fetchButton.innerHTML;

        // Event listener used when the cover image is loaded
        // from archive.org.
        // Restore the button that was put in a loading state,
        // set the file input value to null and also the flag
        // to destroy the cover
        const imgLoadEventListener = (evt) => {
            fetchButton.innerHTML = oldButtonHTML;
            fetchButton.disabled = false;

            fileUpload.value = null;
            destroyImageInput.value = '';
            useArchiveOrgCoverInput.value = 'true';

            if (evt.type === 'error') {
                alert('Error fetching cover from archive.org');
            }
        };

        // When clicking on the fecth button, display a spinner while
        // the cover image SRC is replaced with the Archive URL.
        fetchButton.addEventListener('click', (evt) => {
            const url = document.getElementById('archiveorg_url').value;
            const match = url.match(REGEX_ARCHIVE_ORG);
            if (match && match[1]) {
                evt.target.disabled = true;
                evt.target.innerHTML = 'Fetching… <i class="fa-solid fa-spinner fa-spin"></i>';

                coverElement.addEventListener('load', imgLoadEventListener);
                coverElement.addEventListener('error', imgLoadEventListener);

                const id = match[1];
                const coverUrl = `https://archive.org/download/${id}/page/cover_w600.jpg`;
                coverElement.src = coverUrl;
            } else {
                alert('Missing or invalid archive.org URL');
            }
        });

        // when the file upload is used, display the selected
        // file as a thumbnail
        fileUpload.addEventListener('change', () => {
            const file = fileUpload.files[0];
            const reader = new FileReader();
            reader.addEventListener('loadend', () => {
                coverElement.src = reader.result;
            });

            if (file) {
                coverElement.removeEventListener('load', imgLoadEventListener);
                reader.readAsDataURL(file);
                useArchiveOrgCoverInput.value = '';
                destroyImageInput.value = '';
            }
        });

        // When clicking on the image destroy button, set the relevant flags
        // and clear the file input
        document.getElementById('destroy-image-button')
            .addEventListener('click', () => {
                coverElement.src = '/images/no-cover.svg';
                useArchiveOrgCoverInput.value = '';
                fileUpload.value = null;
                destroyImageInput.value = 'true';
            });
    }

    // This event is to re-attach the autocomplete control
    // to the MagazineIndex liveweire component for the game and
    // software input. Since livewire will dynaically add rows to the DOM
    // without this the input will not get the autocomplete as it's
    // attached only once on page load
    window.Livewire.hook('message.processed', () => {
        setTimeout(() => {
            window.document.dispatchEvent(
                new CustomEvent('atarilegend:autocomplete', {
                    bubbles: true,
                    cancelable: true,
                })
            );
        }, 50);
    });
});
