import axios from 'axios';
import * as bootstrap from 'bootstrap';

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });

document.addEventListener('DOMContentLoaded', () => {

    // Enable popovers globally
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        var options = {};
        if (el.dataset.bsContentSelector) {
            // If there's a data-bs-content-selector attribute, use the
            // target element HTML as the popover content
            options.content = document.querySelector(el.dataset.bsContentSelector).innerHTML;
        }
        new bootstrap.Popover(el, options);
    });

    // Enable toasts globally
    document.querySelectorAll('.toast').forEach(el => {
        new bootstrap.Toast(el).show();
    });
});
