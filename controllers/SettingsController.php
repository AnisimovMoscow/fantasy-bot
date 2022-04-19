<?php

namespace app\controllers;

use app\components\Telegram;
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
        $query = Yii::$app->request->post('data');
        parse_str($query, $data);
        Yii::info('data' . print_r($data, true), 'send');
        $result = Telegram::validate($data);

        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['ok' => $result];
    }
}
