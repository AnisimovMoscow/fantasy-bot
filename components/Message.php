<?php

namespace app\components;

use Yii;
use yii\base\BaseObject;
use app\models\User;
use TelegramBot\Api\BotApi;
use Exception;

class Message extends BaseObject
{
    /**
     * Отправляет сообщение пользователю
     * 
     * @param int|string $chatId
     * @param string $text
     * @param User|null $user
     * @param string|null $site
     * @param string|null $token
     */
    public static function send($chatId, $text, $user, $site, $token = null)
    {
        if (empty($token)) {
            $token = Yii::$app->params['token'][$site];
        }
        $bot = new BotApi($token);
        try {
            $bot->sendMessage($chatId, $text);
        } catch (Exception $e) {
            Yii::info('Send error', 'send');
            Yii::info('Message: '.$e->getMessage(), 'send');
            Yii::info('Code: '.$e->getCode(), 'send');
            /*
            if ($user !== nill && $e->getCode() == 0) {
                $user->notification = false;
                $user->save();
            }
            */
        }
    }
}
