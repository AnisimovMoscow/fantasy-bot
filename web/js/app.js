window.app = window.app || {};

app.init = function () {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/settings/load');
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
            var json = JSON.parse(xhr.response);
            if (json.ok) {
                app.fill(json.settings);
            }
        }
    }

    xhr.send(JSON.stringify({
        'data': window.Telegram.WebApp.initData
    }));
};

app.fill = function (settings) {
    if (settings.notification) {
        document.getElementById('notification').checked = true;
    }

    document.getElementById('time').value = settings.notificationTime;

    var group = app.getGroup(settings.timezone);
    document.getElementById('group').value = group;
    document.getElementById('timezone').value = settings.timezone;
};

app.getGroup = function (timezone) {
    for (const [group, timezones] of Object.entries(app.groups)) {
        timezones.forEach(function (tz) {
            if (tz.name == timezone) {
                return group;
            }
        });
    }
    return null;
};

app.status = function (message) {
    document.getElementById('status').innerHTML += message;
    document.getElementById('status').innerHTML += '<br>';
};

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('group').addEventListener('change', function () {
        var options = document.querySelectorAll('#timezone option');
        options.forEach(o => o.remove());

        var select = document.getElementById('timezone');
        app.groups[this.value].forEach(function (timezone) {
            var option = document.createElement('option');
            option.value = timezone.name;
            option.innerHTML = timezone.location;
            select.appendChild(option);
        });
    });

    app.init();
});
