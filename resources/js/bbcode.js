document.addEventListener('DOMContentLoaded', () => {

    /**
     * Handle insertion of BBCode tags into textareas
     */
    document.querySelectorAll('[data-bbcode-target]').forEach(el => {
        el.addEventListener('click', () => {
            var openingTag = el.dataset.bbcodeTag;
            var closingTag = el.dataset.bbcodeTag.split('=')[0];
            var targetEl = document.querySelector(el.dataset.bbcodeTarget);

            if (targetEl.selectionStart !== targetEl.selectionEnd) {
                // Text is selected. Insert the opening tag at the beginning of
                // selection and the closing tag at the end
                targetEl.setRangeText(`[${openingTag}]`, targetEl.selectionStart, targetEl.selectionStart);
                targetEl.setRangeText(`[/${closingTag}]`, targetEl.selectionEnd, targetEl.selectionEnd, 'end');
            } else {
                // No selection, insert wherever the cursor is
                targetEl.setRangeText(`[${openingTag}][/${closingTag}]`, targetEl.selectionStart,
                    targetEl.selectionEnd, 'end');
            }

            // Put back the cursor on the textarea
            targetEl.focus();
        });
    });

});
