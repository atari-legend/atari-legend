document.addEventListener('DOMContentLoaded', () => {
    function ellipsizeTextBox(el) {
        var wordArray = el.innerHTML.split(' ');
        while (el.scrollHeight > el.offsetHeight && wordArray.length > 0) {
            wordArray.pop();
            el.innerHTML = wordArray.join(' ') + '\u2026';
        }
    }

    document.querySelectorAll('.ellipsis').forEach(el => {
        ellipsizeTextBox(el);
    });
});
