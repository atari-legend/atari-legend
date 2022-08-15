const axios = require('axios');
const REGEX_ARCHIVE_ORG = new RegExp('https://archive.org/details/[^/]+/');

document.addEventListener('DOMContentLoaded', () => {
    var btn = document.getElementById('fetch-thumbnail');
    if (btn) {
        btn.addEventListener('click', () => {
            var url = document.getElementById('archiveorg_url').value;
            if (REGEX_ARCHIVE_ORG.test(url)) {
                var id = url.split('/')[4];
                axios
                    .post(`./image?id=${id}`)
                    .then((resp) => {
                        document.getElementById('issue-cover').src = resp.data + '?t=' + Date.now();
                    })
                    .catch((err) => {
                        alert(`Error fetching cover: ${err}`);
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
