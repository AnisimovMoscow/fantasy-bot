<?php
namespace app\commands;

use app\components\Message;
use app\models\Team;
use app\models\Tournament;
use app\models\User;
use DateInterval;
use DateTime;
use DateTimeZone;
use Yii;
use yii\console\Controller;

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
        echo "Обновление дедлайнов\n";

        $tournaments = Tournament::find()->all();
        foreach ($tournaments as $tournament) {
            echo "{$tournament->name}\n";

            $team = Team::findOne(['tournament_id' => $tournament->id]);
            if ($team !== null) {
                $deadline = $team->getDeadline();
                echo "Дедлайн: {$deadline['deadline']}\n";

                if (!empty($deadline['deadline']) && $tournament->deadline != $deadline['deadline']) {
                    $tournament->deadline = $deadline['deadline'];
                    if (empty($deadline['transfers'])) {
                        $tournament->checked = true;
                    } else {
                        $tournament->checked = false;
                        $tournament->transfers = $deadline['transfers'];
                    }
                    $tournament->save();
                    echo "Обновлён\n";
                }
            }
        }
    }

    /**
     * Отравка сообщения о сегодняшних дедлайнах
     */
    public function actionToday()
    {
        echo "Время: " . date('r') . "\n";

        $format = 'Y-m-d H:i:s';
        $interval = new DateInterval('PT5M');
        $time = new DateTime();
        $time->sub($interval);
        $userAfter = $time->format($format);
        echo "userAfter: {$userAfter}\n";

        $time = new DateTime();
        $time->add($interval);
        $userBefore = $time->format($format);
        echo "userBefore: {$userBefore}\n";

        $time = new DateTime();
        $deadlineAfter = $time->format($format);
        echo "deadlineAfter: {$deadlineAfter}\n";

        $interval = new DateInterval('P1D');
        $time = new DateTime();
        $time->add($interval);
        $deadlineBefore = $time->format($format);
        echo "deadlineBefore: {$deadlineBefore}\n";

        $users = User::find()->where("notification = 1 AND (TIMESTAMP(CURDATE(), notification_time) BETWEEN '{$userAfter}' AND '{$userBefore}')")->all();
        foreach ($users as $user) {
            echo "User {$user->id} {$user->notification_time}\n";

            $deadlines = [];
            foreach ($user->teams as $team) {
                if ($team->tournament->deadline > $deadlineAfter && $team->tournament->deadline < $deadlineBefore) {
                    $deadlines[$team->tournament->name] = $team->tournament->deadline;
                }
            }

            if (!empty($deadlines)) {
                asort($deadlines);
                $message = 'Сегодня дедлайны:';
                foreach ($deadlines as $name => $deadline) {
                    $date = new DateTime($deadline, new DateTimeZone(Yii::$app->timeZone));
                    $date->setTimezone(new DateTimeZone($user->timezone));
                    $message .= "\n" . $date->format('H:i') . '  ' . $name;
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
        $time = time() + 2 * 60 * 60;

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
                        $date->setTimezone(new DateTimeZone($team->user->timezone));
                        $message .= "\n" . $date->format('H:i') . '  ' . $tournament->name;
                        Message::send($team->user->chat_id, $message, $team->user, $team->user->site);
                    }
                }
            }
        }
    }
}
