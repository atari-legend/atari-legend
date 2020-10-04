document.addEventListener('DOMContentLoaded', () => {

    /**
     * Handle inline editing of comments
     */
    document.querySelectorAll('[data-comment-edit]').forEach(el => {
        var commentId = el.dataset.commentEdit;

        // The 'edit' button can be clicked either to edit a comment, or to
        // cancel editing
        el.addEventListener('click', e => {

            // Toggle display of the comment text / comment edit form
            document.getElementById(`comment-${commentId}`).classList.toggle('d-none');
            document.getElementById(`comment-edit-${commentId}`).classList.toggle('d-none');

            // Toggle display of save button
            document.querySelector(`[data-comment-save="${commentId}"]`).classList.toggle('d-none');

            // Convert the edit button into a cancel one and vice-versa
            var icon = el.querySelector('i');
            ['fas', 'fas-pencil-alt', 'far', 'fa-window-close', 'text-muted']
                .forEach(className => icon.classList.toggle(className));

            e.preventDefault();
        });
    });
});

