<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\Tournament;
use app\models\Team;
use app\models\User;
use app\components\Message;
use DateTime;
use DateTimeZone;

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
        foreach ($tournaments as $tournament) {
            $team = Team::findOne(['tournament_id' => $tournament->id]);
            if ($team !== null) {
                $deadline = $team->getDeadline();
                if (!empty($deadline['deadline']) && $tournament->deadline != $deadline['deadline']) {
                    $tournament->deadline = $deadline['deadline'];
                    if (empty($deadline['transfers'])) {
                        $tournament->checked = true;
                    } else {
                        $tournament->checked = false;
                        $tournament->transfers = $deadline['transfers'];
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
                
                Message::send($user->chat_id, $message, $user, $user->site);
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
            $tournament->checked = true;
            $tournament->save();
            foreach ($tournament->teams as $team) {
                if ($team->user->notification) {
                    $transfers = $team->getTransfers();
                    if ($transfers == $tournament->transfers) {
                        $message = 'Ты ещё не сделал замены, скоро дедлайн:';
                        $date = new DateTime($tournament->deadline, new DateTimeZone(Yii::$app->timeZone));
                        $message .= "\n".$date->format('H:i').'  '.$tournament->name;
                        Message::send($team->user->chat_id, $message, $team->user, $team->user->site);
                    }
                }
            }
        }
    }
}
