<?php

namespace app\components;

use app\models\User;
use Exception;
use TelegramBot\Api\BotApi;
use Yii;
use yii\base\BaseObject;

class Message extends BaseObject
{
    /**
     * Отправляет сообщение пользователю
     *
     * @param int|string $chatId
     * @param string $text
     * @param User|null $user
     * @param string|null $token
     * @param InlineKeyboardMarkup|null $keyboard
     */
    public static function send($chatId, $text, $user, $token = null, $keyboard = null)
    {
        if (empty($token)) {
            $token = Yii::$app->params['token'];
        }
        $bot = new BotApi($token);
        try {
            $bot->sendMessage($chatId, $text, null, false, null, $keyboard);
        } catch (Exception $e) {
            Yii::info('Send error. Message: ' . $e->getMessage() . ' Code: ' . $e->getCode() . ' User: ' . (($user === null) ? 'null' : $user->id), 'send');

            if ($user !== null && in_array($e->getCode(), [400, 403])) {
                $user->notification = false;
                $user->save();
            }
        }
    }
}
