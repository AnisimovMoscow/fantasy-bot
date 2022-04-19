<?php

namespace app\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class SettingsController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'load' => ['post'],
                ],
            ],
        ];
    }

    public function actionApp()
    {
        return $this->render('app');
    }

    public function actionLoad()
    {
        $data = Yii::$app->request->post();
        Yii::info('data' . print_r($data, true), 'send');

        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['ok' => true];
    }
}
