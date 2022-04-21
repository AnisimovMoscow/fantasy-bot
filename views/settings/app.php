<?php

/* @var $this yii\web\View */
/* @var $groups array */

$this->title = 'Настройки';
?>
<h1>Настройки</h1>

<form>
    <div class="row check">
        <input type="checkbox" class="check-input" id="notification" name="notification">
        <label for="notification">Присылать уведомления</label>
    </div>

    <div class="row">
        <label for="group" class="label">Часовой пояс:</label>
        <select class="control" id="group" name="group">
        <?php foreach ($groups as $group => $list): ?>
            <option value="<?=$group?>"><?=$group?></option>
        <?php endforeach;?>
        </select>
        <select class="control" id="timezone" name="timezone">
        </select>
    </div>

    <div class="row">
        <label for="time" class="label">Время уведомлений:</label>
        <input type="time" class="control" id="time" name="time" value="00:00">
    </div>

    <button type="submit">Сохранить</button>
</form>

<p id="status"></p>

<script>
    window.app = window.app || {};
    app.groups = <?=json_encode($groups)?>;
</script>