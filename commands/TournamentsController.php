<?php
namespace app\commands;

use app\components\Message;
use app\components\Sports;
use app\components\SportsLegacy;
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
            echo $tournament->name;
            $isLegacy = in_array($tournament->webname, SportsLegacy::TOURNAMENTS);

            $team = Team::findOne(['tournament_id' => $tournament->id]);
            if ($team !== null) {
                $deadline = $isLegacy ? SportsLegacy::getTeamDeadline($team->sports_id) : Sports::getTeamDeadline($team->sports_id);
                if ($deadline === null) {
                    echo ' - Дедлайн не известен';
                    continue;
                }

                $time = strtotime($deadline);
                $deadline = date('Y-m-d H:i:s', $time);
                echo " - Дедлайн: {$deadline}";
                $now = time();

                if ($time < $now) {
                    $tournament->deadline = null;
                    $tournament->save();
                    echo ' - Завершён';
                } elseif ($tournament->deadline != $deadline) {
                    $tournament->deadline = $deadline;
                    $transfers = $isLegacy ? SportsLegacy::getTeamTotalTransfers($team->sports_id) : Sports::getTeamTotalTransfers($team->sports_id);
                    $tournament->checked = false;
                    $tournament->transfers = $transfers;
                    $tournament->save();
                    echo ' - Обновлён';
                }
            }

            echo "\n";
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

                Message::send($user->chat_id, $message, $user);
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
            $isLegacy = in_array($tournament->webname, SportsLegacy::TOURNAMENTS);
            $tournament->checked = true;
            $tournament->save();
            foreach ($tournament->teams as $team) {
                if ($team->user->notification) {
                    $transfers = $isLegacy ? SportsLegacy::getTeamTransfersLeft($team->sports_id) : Sports::getTeamTransfersLeft($team->sports_id);
                    if ($transfers == $tournament->transfers) {
                        $message = 'Ты ещё не сделал замены, скоро дедлайн:';
                        $date = new DateTime($tournament->deadline, new DateTimeZone(Yii::$app->timeZone));
                        $date->setTimezone(new DateTimeZone($team->user->timezone));
                        $message .= "\n" . $date->format('H:i') . '  ' . $tournament->name;
                        Message::send($team->user->chat_id, $message, $team->user);
                    }
                }
            }
        }
    }
}
