/**
 * Handle toggling of labels for collapse controls
 */
document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.collapse').forEach(el => {
        // Retrieve the current text of the collapse control and store it in
        // a dat attribute
        document.querySelectorAll(`[data-bs-toggle][href="#${el.id}"`).forEach(triggerEl => {
            triggerEl.dataset.alOriginalText = triggerEl.innerHTML;
        });

        // When the collapsed content is shown, change the collapse control text
        el.addEventListener('shown.bs.collapse', () => {
            document.querySelectorAll(`[data-bs-toggle][href="#${el.id}"`).forEach(triggerEl => {
                triggerEl.innerHTML = triggerEl.dataset.alCollapsedText;
            });
        });

        // When the collapsed content is hidden, restore the collapse control text
        el.addEventListener('hidden.bs.collapse', () => {
            document.querySelectorAll(`[data-bs-toggle][href="#${el.id}"`).forEach(triggerEl => {
                triggerEl.innerHTML = triggerEl.dataset.alOriginalText;
            });
        });
    });
});
