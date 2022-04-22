<?php

namespace app\controllers;

use app\components\Telegram;
use app\models\Timezone;
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
                    'save' => ['post'],
                ],
            ],
        ];
    }

    public function actionApp()
    {
        $groups = Timezone::getAll();
        return $this->render('app', ['groups' => $groups]);
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
                'notificationTime' => $user->notification_time,
                'timezone' => $user->timezone,
            ],
        ];
    }

    public function actionSave()
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

        $settings = Yii::$app->request->post('settings');
        $result = $user->saveSettings($settings);

        return ['ok' => $result];
    }
}
