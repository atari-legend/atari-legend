document.addEventListener('DOMContentLoaded', () => {

    // Handle button to generate URL slug from game name
    document.querySelectorAll('#generate-slug').forEach(el => {
        el.addEventListener('click', () => {
            var name = document.getElementById('name').value || '';
            document.getElementById('slug').value = name.toLowerCase()
                .trim()
                .replace(/[^a-z0-9-]+/g, '-');
        });
    });
});
