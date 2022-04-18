<?php

namespace app\controllers;

use yii\web\Controller;

class SettingsController extends Controller
{
    public function actionApp()
    {
        return $this->render('app');
    }
}
