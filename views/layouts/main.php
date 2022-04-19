<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;

?>
<?php $this->beginPage()?>
<!DOCTYPE html>
<html lang="<?=Yii::$app->language?>">
<head>
    <meta charset="<?=Yii::$app->charset?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=Html::encode($this->title)?></title>
    <link href="/css/app.css" rel="stylesheet">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="/js/app.js"></script>
    <?php $this->head()?>
</head>
<body>
<?php $this->beginBody()?>
<?=$content?>
<?php $this->endBody()?>
</body>
</html>
<?php $this->endPage()?>
