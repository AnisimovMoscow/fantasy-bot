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
            Yii::info('Send error. Message: '.$e->getMessage().' Code: '.$e->getCode().' User: '.(($user === null) ? 'null' : $user->id), 'send');
            
            if ($user !== null && in_array($e->getCode(), [400, 403])) {
                $user->notification = false;
                $user->save();
            }
        }
    }
}
