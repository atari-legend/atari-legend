document.addEventListener('DOMContentLoaded', () => {

    /**
     * Handle deletion of the avatar in the user profile
     */
    document.querySelectorAll('#delete-avatar').forEach(el => {
        el.addEventListener('click', e => {
            // Remove image from the DOM
            document.querySelector('#avatar-image').remove();
            // Flag avatar as removed
            document.querySelector('#avatar-removed').value = 'on';

            // Prevent anchor link
            e.preventDefault();

            // Remove the trash icon from the DOM
            el.remove();
        });
    });
});
