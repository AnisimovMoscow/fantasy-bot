<?php

/* @var $this yii\web\View */

$this->title = 'Настройки';
?>
<h1>Настройки</h1>

<form>
    <div class="row check">
        <input type="checkbox" class="check-input" id="notification" name="notification">
        <label for="notification">Присылать уведомления</label>
    </div>

    <div class="row">
        <label for="region" class="label">Часовой пояс:</label>
        <select name="region" class="control">
            <option value="Europe">Europe</option>
        </select>
        <select name="timezone" class="control">
            <option value="Moscow">Moscow (21:25)</option>
        </select>
    </div>

    <div class="row">
        <label for="time" class="label">Время уведомлений:</label>
        <input type="time" class="control" id="time" name="time" value="11:00">
    </div>

    <button type="submit">Сохранить</button>
</form>

<p id="status"></p>