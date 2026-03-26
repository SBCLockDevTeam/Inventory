/**
 * ajax_helpers.js
 * Lightweight AJAX utility used across the application.
 */

/**
 * Send a JSON POST request.
 *
 * @param {string} url
 * @param {object} data  - Plain object; serialised to JSON.
 * @returns {Promise<object>} Parsed JSON response body.
 * @throws On network error or non-2xx HTTP status.
 */
async function postJSON(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data),
    });
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    return response.json();
}

/**
 * Send a GET request and return parsed JSON.
 *
 * @param {string} url
 * @param {object} [params={}] - Key/value pairs appended as query string.
 * @returns {Promise<object>}
 */
async function getJSON(url, params = {}) {
    const qs = new URLSearchParams(params).toString();
    const fullUrl = qs ? `${url}?${qs}` : url;
    const response = await fetch(fullUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    return response.json();
}