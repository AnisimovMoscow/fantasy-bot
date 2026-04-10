<?php
namespace app\commands;

use Exception;
use TelegramBot\Api\BotApi;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Команды для работы с телеграм
 */
class TelegramController extends Controller
{
    private const CHAT_ID = 814485; // тестовый юзер

    /**
     * Тестирование отправки
     */
    public function actionTest($proxy = "")
    {
        $time = Yii::$app->formatter->asDatetime('now');
        $text = "Проверка отправки сообщения ({$time})\n";

        $token = Yii::$app->params['token'];
        $bot = new BotApi($token);

        if (!empty($proxy)) {
            $bot->setProxy($proxy, true);
        }

        echo "Отправка...\n";
        try {
            $bot->sendMessage(self::CHAT_ID, $text);
            echo "Отправлено сообщение\n";
        } catch (Exception $e) {
            echo "Ошибка при отправке\n";
            echo $e->getMessage() . "\n";
            echo "Code: " . $e->getCode() . "\n";
        }

        return ExitCode::OK;
    }
}
