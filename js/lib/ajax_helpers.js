// AJAX Helper Functions
function makeRequest(method, url, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (callback) callback(xhr.status, xhr.responseText);
    };
    xhr.onerror = function() {
        if (callback) callback(xhr.status, null);
    };
    xhr.send(data ? JSON.stringify(data) : null);
}
