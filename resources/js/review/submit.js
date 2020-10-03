var BBCodeParser = require('bbcode-parser');

document.addEventListener('DOMContentLoaded', () => {

    /**
     * Handle previewing of submitted reviews
     */
    document.querySelector('a[data-toggle="tab"][href="#preview"')
        // When the preview tab is selected...
        .addEventListener('show.bs.tab', () => {
            // Convert the BBCode into HTML and inject in the preview area

            // Only allow some tags, not all the default ones from the BBCode parser
            var permittedTags = ['b', 'u', 'i', 'url'];
            var allTags = BBCodeParser.defaultTags();

            var tags = Object.keys(allTags)
                .filter(tag => permittedTags.includes(tag))
                .reduce((obj, key) => {
                    console.log(obj, key);
                    obj[key] = allTags[key];
                    return obj;
                }, {});

            var parser = new BBCodeParser(tags);
            var html = parser.parseString(document.querySelector('#text').value);
            document.querySelector('#preview-text').innerHTML = html;

            // Copy all values (score, screenshot) to the preview
            document.querySelectorAll('.previewable').forEach(el => {
                document.querySelector(`#preview-${el.id}`).innerText = el.value;
            });
        });
});
