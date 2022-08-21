const axios = require('axios');
const REGEX_ARCHIVE_ORG = new RegExp('https://archive.org/details/[^/]+/$');

document.addEventListener('DOMContentLoaded', () => {
    var btn = document.getElementById('fetch-thumbnail');
    if (btn) {
        btn.addEventListener('click', (evt) => {
            var url = document.getElementById('archiveorg_url').value;
            if (REGEX_ARCHIVE_ORG.test(url)) {
                evt.target.disabled = true;
                const oldButtonHTML = evt.target.innerHTML;
                evt.target.innerHTML = 'Fetching… <i class="fa-solid fa-spinner fa-spin"></i>';

                var id = url.split('/')[4];
                axios
                    .post(`./image?id=${id}`)
                    .then((resp) => {
                        document.getElementById('issue-cover').src = resp.data + '?t=' + Date.now();
                    })
                    .catch((err) => {
                        alert(`Error fetching cover: ${err}`);
                    })
                    .then(() => {
                        evt.target.innerHTML = oldButtonHTML;
                        evt.target.disabled = false;
                    });
            } else {
                alert('Missing or invalid archive.org URL');
            }
        });
    }

    window.Livewire.hook('message.processed', () => {
        setTimeout(() => {
            window.document.dispatchEvent(new CustomEvent('atarilegend:autocomplete', {
                bubbles: true,
                cancelable: true,
            }));
        }, 50);
    });
});
