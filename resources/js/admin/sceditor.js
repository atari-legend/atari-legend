document.addEventListener('DOMContentLoaded', () => {

    /**
     * Create a custom command for the toolbar, with a popup asking for
     * an ID and the text.
     *
     * @param {*} code BBCode for the command, e.g. 'game'
     * @param {*} desc Description of the input value, e.g. 'Game ID'
     * @param {*} tooltip Tooltip for the button, e.g. "Insert a game link"
     * @returns Custom command
     */
    function customCommand(code, desc, tooltip) {
        return {
            exec: function(caller) {
                var editor = this;

                // Check if text was aleady selected when the button
                // was clicked
                var value = '';
                if (editor.getRangeHelper().selectedHtml()) {
                    value = editor.getRangeHelper().selectedHtml();
                }

                var content = document.createElement('div');
                content.innerHTML = `
                        ${desc}: <input type="number" name="${code}" size="5" /><br />
                        Text: <input type="text" name="text" size="10" value="${value}" /><br />
                        <button type="button" class="button">Insert</button>`;
                var id = content.querySelector(`input[name=${code}]`);
                var txt = content.querySelector('input[name=text]');
                content.querySelector('button').onclick = () => {
                    editor.insert(`[${code}=${id.value}]${txt.value}[/${code}]`);
                    editor.closeDropDown();
                };

                editor.createDropDown(caller, `insert${code}`, content);
            },
            tooltip: tooltip,
        };
    }

    /**
     * Create the toolbar command to insert a release year
     * @returns Custom command
     */
    function releaseYearCommand() {
        return {
            exec: function (caller) {
                var editor = this;

                // Check if text was aleady selected when the button
                // was clicked
                var value = '';
                if (editor.getRangeHelper().selectedHtml()) {
                    value = editor.getRangeHelper().selectedHtml();
                }

                var content = document.createElement('div');
                content.innerHTML = `
                        Year: <input type="number" name="releaseyear" value="${value}" size="5" /><br />
                        <button type="button" class="button">Insert</button>`;
                var year = content.querySelector('input[name=releaseyear]');
                content.querySelector('button').onclick = () => {
                    editor.insert(`[releaseyear]${year.value}[/releaseyear]`);
                    editor.closeDropDown();
                };

                editor.createDropDown(caller, 'insertreleaseyear', content);
            }
        };
    }

    /**
     * Create a custom BBcode
     * @param {*} code Name of the code e.g, 'game'
     * @param {*} search If set, use the game search URL with that parameter, otherwise
     *                   make a URL to "/${code}s: (e.g. "/games")
     * @returns Custom BB Code
     */
    function customCode(code, search) {
        var url = `/${code}s/`;
        if (search) {
            url = `/games/search?${search}=`;
        }

        // Declare which attribute we respond to
        var a = {};
        a[`data-al-${code}-id`] = null;

        return {
            tags: {
                a: a,
            },
            allowedChildren: [],
            allowEmpty: false,
            isSelfClosing: false,
            format: function(elm) {
                return `[${code}=` + elm.getAttribute(`data-al-${code}-id`) + `]${elm.textContent}[/${code}]`;
            },
            html: function (token, attrs, content) {
                return `<a href="${url}${attrs.defaultattr}" `
                    + `data-al-${code}-id="${attrs.defaultattr}">${content}</a>`;
            }
        };
    }

    /**
     * Create a custom BBCode for release year
     * @returns Custom release year BBCode
     */
    function releaseYearCode() {
        // Declare which attribute we respond to
        const a = {};
        a['data-al-releaseyear-id'] = null;

        return {
            tags: {
                a: a,
            },
            allowedChildren: [],
            allowEmpty: false,
            isSelfClosing: false,
            format: function(elm) {
                return `[releaseyear]${elm.textContent}[/releaseyear]`;
            },
            html: function (token, attrs, content) {
                return `<a href="/games/search?year=${content}" `
                    + `data-al-releaseyear-id="${content}">${content}</a>`;
            }
        };
    }

    // Add all toolbar commands
    window.sceditor.command.set('game', customCommand('game', 'Game ID', 'Insert link to a game'));
    window.sceditor.command.set('review', customCommand('review', 'Review ID', 'Insert link to a review'));
    window.sceditor.command.set('article', customCommand('article', 'Article ID', 'Insert link to an article'));
    window.sceditor.command.set('interview', customCommand('interview', 'Interview ID', 'Insert link to an interview'));
    window.sceditor.command.set('menuset', customCommand('menuset', 'Menuset ID', 'Insert link to a menu set'));
    window.sceditor.command.set('magazine', customCommand('magazine', 'Magazine ID', 'Insert link to a magazine'));
    window.sceditor
        .command.set('releaseyear', releaseYearCommand());
    window.sceditor
        .command.set('publisher', customCommand('publisher', 'Publisher ID', 'Insert link to a publisher'));
    window.sceditor
        .command.set('developer', customCommand('developer', 'Developer ID', 'Insert link to a developer'));
    window.sceditor
        .command.set('individual', customCommand('individual', 'Individual ID', 'Insert link to an individual'));


    // Add "simple" custom BBCodes
    var codes = [
        'game', 'review', 'article', 'interview', 'menuset', 'magazine'
    ];
    codes.forEach(it => window.sceditor.formats.bbcode.set(it, customCode(it)));

    // Add BBCode that point to the search results
    var searchCodes = [
        'publisher', 'developer', 'individual'
    ];
    searchCodes.forEach(code => window.sceditor.formats.bbcode.set(code, customCode(code, code + '_id')));

    // Add releaseyear BBCode
    window.sceditor.formats.bbcode.set('releaseyear', releaseYearCode());

    // Override the [url] tag as it will conflict with ou custom tags
    // like [game], etc. that also use <a href="...">tags</a>
    // See: https://github.com/samclarke/SCEditor/issues/872
    window.sceditor.formats.bbcode.set('url', {
        format: function (element, content) {
            if (element.getAttributeNames().some(elem => /data-al-\w+-id/.test(elem))) {
                // Ignore our own custom tags that will all have a data-al-something-id attribute
                return;
            }

            var url = element.getAttribute('href');
            return `[url=${url}]${content}[/url]`;
        }
    });

    document.querySelectorAll('textarea.sceditor').forEach((el) => {
        window.sceditor.create(el, {
            format: 'bbcode',
            style: '/css/sceditor-iframe.css',
            toolbar: 'bold,italic,underline,color|'
                + [...codes, ...searchCodes, 'releaseyear'].join(',')
                + '|removeformat|cut,copy,paste,pastetext|'
                + 'image,link,unlink,emoticon|maximize,source',
            emoticonsRoot: '/images/smilies/',
            emoticons: {
                dropdown: {
                    ':-D': 'icon_e_biggrin.gif',
                    ':)': 'icon_e_smile.gif',
                    ':(': 'icon_e_sad.gif',
                    '8O': 'icon_eek.gif',
                    ':?': 'icon_e_confused.gif',
                    ' 8)': 'icon_cool.gif',
                    ':x': 'icon_mad.gif',
                    ':P': 'icon_razz.gif',
                    ':oops:': 'icon_redface.gif',
                    ':evil:': 'icon_evil.gif',
                    ':twisted:': 'icon_twisted.gif',
                    ':roll:': 'icon_rolleyes.gif',
                    ':frown:': 'icon_frown.gif',
                    ':|': 'icon_neutral.gif',
                    ':mrgreen:': 'icon_mrgreen.gif',
                    ':o': 'icon_e_surprised.gif',
                    ':lol:': 'icon_lol.gif',
                    ':cry:': 'icon_cry.gif',
                    ';)': 'icon_e_wink.gif',
                    ':wink:': 'icon_e_wink.gif',
                    ':!:': 'icon_exclaim.gif',
                    ':arrow:': 'icon_arrow.gif',
                    ':?:': 'icon_question.gif',
                    ':idea:': 'icon_idea.gif',

                }
            }
        });
    });
});
