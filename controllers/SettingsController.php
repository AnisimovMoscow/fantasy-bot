<?php

namespace app\controllers;

use app\components\Telegram;
use app\models\User;
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
        Yii::$app->response->format = Response::FORMAT_JSON;
        $query = Yii::$app->request->post('data');
        if ($query == '') {
            return ['ok' => false];
        }

        parse_str($query, $data);
        $result = Telegram::validate($data);
        if (!$result) {
            return ['ok' => false];
        }

        $json = json_decode($data['user']);
        $user = User::findOne(['chat_id' => $json->id, 'site' => 'ru']);
        if ($user === null) {
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'settings' => [
                'notification' => $user->notification,
                'notification_time' => $user->notification_time,
                'timezone' => $user->timezone,
            ],
        ];
    }
}
