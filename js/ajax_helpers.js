'use strict';

/**
 * AJAX Utility Functions
 *
 * This module provides a set of utility functions for making AJAX requests
 * and handling JSON data.
 */

/**
 * Make an AJAX GET request.
 * @param {string} url - The URL to make the request to.
 * @returns {Promise<Object>} - A promise that resolves to the response data.
 */
function ajaxGet(url) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject(new Error(`Request failed with status ${xhr.status}`));
            }
        };
        xhr.onerror = () => reject(new Error('Network error'));
        xhr.send();
    });
}

/**
 * Make an AJAX POST request.
 * @param {string} url - The URL to make the request to.
 * @param {Object} data - The data to send with the request.
 * @returns {Promise<Object>} - A promise that resolves to the response data.
 */
function ajaxPost(url, data) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject(new Error(`Request failed with status ${xhr.status}`));
            }
        };
        xhr.onerror = () => reject(new Error('Network error'));
        xhr.send(JSON.stringify(data));
    });
}

/**
 * Handle JSON response.
 * @param {string} responseText - The JSON response as text.
 * @returns {Object} - The parsed JSON object.
 */
function handleJsonResponse(responseText) {
    try {
        return JSON.parse(responseText);
    } catch (error) {
        throw new Error('Invalid JSON response');
    }
}

export { ajaxGet, ajaxPost, handleJsonResponse };