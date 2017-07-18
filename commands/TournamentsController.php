<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use TelegramBot\Api\BotApi;
use app\models\Tournament;
use app\models\Team;
use app\models\User;
use app\components\Html;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Команды для запуска по расписанию
 */
class TournamentsController extends Controller
{
    /**
     * Обновление дедлайнов турнира
     */
    public function actionDeadlines()
    {
        $tournaments = Tournament::find()->all();
        
        $month = [
            'января' => 1,
            'февраля' => 2,
            'марта' => 3,
            'апреля' => 4,
            'мая' => 5,
            'июня' => 6,
            'июля' => 7,
            'августа' => 8,
            'сентября' => 9,
            'октября' => 10,
            'ноября' => 11,
            'декабря' => 12,
        ];
        
        foreach ($tournaments as $tournament) {
            $team = Team::findOne(['tournament_id' => $tournament->id]);
            if ($team !== null) {
                $dom = Html::load($team->url);
                $tables = $dom->getElementsByTagName('table');
                foreach ($tables as $table) {
                    if ($table->getAttribute('class') == 'profile-table') {
                        $trs = $table->getElementsByTagName('tr');
                        foreach ($trs as $tr) {
                            if ($tr->getElementsByTagName('th')->item(0)->nodeValue == 'Дедлайн') {
                                $deadline = $tr->getElementsByTagName('td')->item(0)->nodeValue;
                                $deadline = str_replace(['|', ':'], ' ', $deadline);
                                $deadline = explode(' ', $deadline);
                                if (count($deadline) == 4) {
                                    $year = date('Y');
                                    $time = mktime($deadline[2], $deadline[3], 0, $month[$deadline[1]], $deadline[0], $year);
                                    $now = time();
                                    if ($time < $now) {
                                        $year++;
                                        $time = mktime($deadline[2], $deadline[3], 0, $month[$deadline[1]], $deadline[0], $year);
                                    }
                                    $deadline = date('Y-m-d H:i:s', $time);
                                }
                            } elseif ($tr->getElementsByTagName('th')->item(0)->nodeValue == 'Трансферы в туре') {
                                $transfers = $tr->getElementsByTagName('td')->item(0)->nodeValue;
                            }
                        }
                    }
                }
                
                if ($tournament->deadline != $deadline) {
                    $tournament->deadline = $deadline;
                    if (isset($transfers)) {
                        $tournament->checked = false;
                        $tournament->transfers = $transfers;
                    } else {
                        $tournament->checked = true;
                    }
                    $tournament->save();
                }
            }
        }
    }
    
    /**
     * Отравка сообщения о сегодняшних дедлайнах
     */
    public function actionToday()
    {
        $start = time();
        $end = strtotime('+1 day');
        
        $users = User::findAll(['notification' => true]);
        foreach ($users as $user) {
            $deadlines = [];
            foreach ($user->teams as $team) {
                $deadline = strtotime($team->tournament->deadline);
                if ($deadline > $start && $deadline < $end) {
                    $deadlines[$team->tournament->name] = $team->tournament->deadline;
                }
            }
            
            if (!empty($deadlines)) {
                asort($deadlines);
                $message = 'Сегодня дедлайны:';
                foreach ($deadlines as $name => $deadline) {
                    $date = new DateTime($deadline, new DateTimeZone(Yii::$app->timeZone));
                    $message .= "\n".$date->format('H:i').'  '.$name;
                }
                
                try {
                    $bot = new BotApi(Yii::$app->params['token'][$user->site]);
                    $bot->sendMessage($user->chat_id, $message);
                } catch (Exception $e) {
                    Yii::info($e->getMessage(), 'send');
                }
            }
        }
    }
    
    /**
     * Проверка замен перед дедлайном
     */
    public function actionCheck()
    {
        $time = time() + 2*60*60;
        
        $tournaments = Tournament::find()
            ->where(['checked' => false])
            ->andWhere(['<', 'deadline', date('Y-m-d H:i:s', $time)])
            ->all();
        foreach ($tournaments as $tournament) {
            foreach ($tournament->teams as $team) {
                if ($team->user->notification) {
                    $transfers = $team->getTransfers();
                    if ($transfers == $tournament->transfers) {
                        $message = 'Ты ещё не сделал замены, скоро дедлайн:';
                        $date = new DateTime($tournament->deadline, new DateTimeZone(Yii::$app->timeZone));
                        $message .= "\n".$date->format('H:i').'  '.$tournament->name;

                        try {
                            $bot = new BotApi(Yii::$app->params['token']);
                            $bot->sendMessage($team->user->chat_id, $message);
                        } catch (Exception $e) {
                            Yii::info($e->getMessage(), 'send');
                        }
                    }
                }
            }
            $tournament->checked = true;
            $tournament->save();
        }
    }
}
