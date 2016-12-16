<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use TelegramBot\Api\BotApi;
use app\models\Url;
use app\components\Html;
use DOMDocument;
use Exception;

/**
 * Отправка сообщений в канал
 */
class ChannelsController extends Controller
{
    /**
     * ЦСКА
     */
    public function actionCskanews() {
        $cska = Yii::$app->params['cska'];
        $bot = new BotApi($cska['token']);
        $host = 'https://www.sports.ru';
        
        $dom = Html::load($cska['html']);
        $divs = $dom->getElementsByTagName('div');
        foreach ($divs as $div) {
            if ($div->getAttribute('class') == 'news') {
                $links  = $div->getElementsByTagName('a');
                foreach ($links as $link) {
                    if ($link->getAttribute('class') == 'short-text') {
                        $url = Url::findOne([
                            'channel' => 'cska',
                            'url' => $host.$link->getAttribute('href'),
                        ]);
                        if ($url === null) {
                            $message = $link->nodeValue."\n".$host.$link->getAttribute('href');
                            try {
                                $bot->sendMessage($cska['chat_id'], $message);
                            } catch (Exception $e) {
                                echo $e->getMessage()."\n";
                            }
                            
                            $url = new Url([
                                'channel' => 'cska',
                                'url' => $host.$link->getAttribute('href'),
                            ]);
                            $url->save();
                        }
                    }
                }
            }
        }
    }
}