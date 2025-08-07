/**
 * Sterilabel Application - Global Helper Functions & Loading Bar Logic
 */

/**
 * Escapes HTML special characters in a string to prevent XSS.
 * @param {string} str The string to escape. Can be null or undefined.
 * @returns {string} The escaped string.
 */
function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[m]);
}


/**
 * =========================================================================
 * Modern Page Loading Bar Controller
 * =========================================================================
 * Controls the thin red loading bar at the top of the page.
 */
(function() {
    const loadingBar = document.getElementById('page-loading-bar');
    if (!loadingBar) return;

    let loadingTimer;

    const startLoading = () => {
        clearTimeout(loadingTimer);
        loadingBar.style.opacity = '1';
        loadingBar.style.width = '0%';
        // Start with a small jump
        setTimeout(() => {
            if (loadingBar.style.opacity === '1') { // Cek jika masih loading
                 loadingBar.style.width = (Math.random() * 20 + 20) + '%'; // 20-40%
            }
        }, 10);
    };

    const finishLoading = () => {
        clearTimeout(loadingTimer);
        loadingBar.style.width = '100%';
        // Tunggu transisi selesai, lalu sembunyikan
        loadingTimer = setTimeout(() => {
            loadingBar.style.opacity = '0';
            // Reset setelah disembunyikan
            setTimeout(() => {
                loadingBar.style.width = '0%';
            }, 500);
        }, 400);
    };

    // --- Page Load Events ---
    document.addEventListener('DOMContentLoaded', startLoading);
    window.addEventListener('load', finishLoading);

    // --- AJAX (Fetch) Events Interceptor ---
    // Menyadap fungsi fetch global untuk mengontrol loading bar secara otomatis
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        startLoading();
        try {
            const response = await originalFetch(...args);
            finishLoading();
            return response;
        } catch (error) {
            finishLoading(); // Selesaikan juga jika ada error jaringan
            throw error;
        }
    };

})();