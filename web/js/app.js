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
        data: window.Telegram.WebApp.initData
    }));
};

app.fill = function (settings) {
    if (settings.notification) {
        document.getElementById('notification').checked = true;
    }

    document.getElementById('time').value = settings.notificationTime;

    var group = app.getGroup(settings.timezone);

    document.getElementById('group').value = group;
    var change = new Event('change');
    document.getElementById('group').dispatchEvent(change);

    document.getElementById('timezone').value = settings.timezone;

    document.getElementById('submit').disabled = false;
};

app.getGroup = function (timezone) {
    var result = null;

    Object.entries(app.groups).forEach(function ([group, timezones]) {
        timezones.forEach(function (tz) {
            if (tz.name == timezone) {
                result = group;
            }
        });
    });

    return result;
};

app.updateTomezones = function (group) {
    var options = document.querySelectorAll('#timezone option');
    options.forEach(o => o.remove());

    var select = document.getElementById('timezone');
    app.groups[group.value].forEach(function (timezone) {
        var option = document.createElement('option');
        option.value = timezone.name;
        option.innerHTML = timezone.location;
        select.appendChild(option);
    });
};

app.save = function () {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/settings/save');
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
            var json = JSON.parse(xhr.response);
            if (json.ok) {
                app.status('Сохранено');
            } else {
                app.status('Ошибка');
            }
        }
    }

    xhr.send(JSON.stringify({
        data: window.Telegram.WebApp.initData,
        settings: {
            notification: document.getElementById('notification').checked,
            timezone: document.getElementById('timezone').value,
            notificationTime: document.getElementById('time').value
        }
    }));
};

app.status = function (message) {
    document.getElementById('status').innerHTML = message;
};

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('group').addEventListener('change', function () {
        app.updateTomezones(this);
    });

    document.getElementById('form').addEventListener('submit', function (event) {
        app.save();
        event.preventDefault();
    });

    app.init();
});
