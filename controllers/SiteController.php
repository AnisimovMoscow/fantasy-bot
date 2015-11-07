<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionHook() {
        $post = Yii::$app->request->post(); 
        Yii::info(print_r($post, true));
    }
}