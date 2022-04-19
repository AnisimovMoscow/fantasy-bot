window.app = window.app || {};

app.init = function () {
    app.status('init');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/settings/load');
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
            app.status(xhr.response);
        }
    }

    app.status('send');
    xhr.send(JSON.stringify({
        'data': window.Telegram.WebApp.initData
    }));
};

app.status = function (message) {
    document.getElementById('status').innerHTML += message;
    document.getElementById('status').innerHTML += '<br>';
};

document.addEventListener('DOMContentLoaded', function () {
    app.init();
});