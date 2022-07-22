<?php
namespace app\commands;

use app\components\Fantasyteams;
use app\components\Message;
use app\models\Team;
use app\models\Tournament;
use app\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Команды для работы с пользователем
 */
class UsersController extends Controller
{
    /**
     * Обновление списка команд
     */
    public function actionUpdate($id)
    {
        $user = User::findOne($id);
        if ($user === null) {
            echo "Неверный id\n";
        } else {
            $user->updateTeams();
            if (count($user->teams) > 0) {
                $message = 'Команды:';
                foreach ($user->teams as $team) {
                    $message .= "\n- " . $team->name . ' (' . $team->tournament->name . ')';
                }
            } else {
                $message = 'Нет команд';
            }
            echo $message . "\n";
        }
    }

    /**
     * Обновляет список команд у всех пользователей
     */
    public function actionUpdateAll()
    {
        $users = User::find()->where(['!=', 'sports_id', ''])->all();
        foreach ($users as $i => $user) {
            echo $i . ' ' . $user->id . ' ' . $user->first_name . ' ' . $user->last_name . "\n";
            $user->updateTeams();
        }
    }

    /**
     * Проверяет замены в команде
     */
    public function actionCheckTeam()
    {
        $time = time() + 30 * 60;

        $tournaments = Tournament::find()
            ->where(['>', 'deadline', date('Y-m-d H:i:s')])
            ->andWhere(['<', 'deadline', date('Y-m-d H:i:s', $time)])
            ->all();

        $cache = Yii::$app->cache;

        foreach (Yii::$app->params['teams'] as $teamParams) {
            foreach ($tournaments as $tournament) {
                $key = $teamParams['slug'] . '_check_' . $tournament->id;
                $check = $cache->get($key);
                if (!$check) {
                    $ft = Fantasyteams::getTeam($teamParams['slug']);
                    if ($ft === null) {
                        continue;
                    }
                    $notChanges = [];
                    foreach ($ft['players'] as $player) {
                        $user = User::findOne(['sports_id' => $player['sports_id']]);
                        if ($user === null) {
                            echo $player['name'] . " - user not found\n";
                            continue;
                        }

                        $team = Team::findOne(['user_id' => $user->id, 'tournament_id' => $tournament->id]);
                        if ($team === null) {
                            echo $player['name'] . " - team not found\n";
                            continue;
                        }

                        $transfers = $team->getTransfers();
                        if ($transfers == $tournament->transfers) {
                            $notChanges[] = empty($user->username) ? $user->first_name . ' ' . $user->last_name : '@' . $user->username;
                        }
                    }

                    $message = $tournament->name . ': ';
                    if (empty($notChanges)) {
                        $message .= 'все сделали замены';
                    } elseif (count($notChanges) == 1) {
                        $message .= $notChanges[0] . ' не сделал замены';
                    } else {
                        $message .= implode(', ', $notChanges) . ' не сделали замены';
                    }

                    Message::send($teamParams['chat_id'], $message, null, $teamParams['token']);

                    $cache->set($key, true, 60 * 60);
                }
            }
        }
    }

    /**
     * Отправляет рассылку пользователям
     */
    public function actionSend()
    {
        $message = "Самый полезный канал по фэнтези Италии - https://t.me/fangazzetta\n\n-коэффициенты\n-статистика\n-составы\n-травмы и дисквалификации";

        $tournament = Tournament::findOne(['webname' => 'italy']);
        if ($tournament === null) {
            echo "Турнир не найден\n";
            return ExitCode::DATAERR;
        }

        $users = User::find()
            ->where(['!=', 'sports_id', ''])
            ->andWhere(['>=', 'id', 800])
            ->andWhere(['<', 'id', 1100])
            ->andWhere(['notification' => true])
            ->all();

        $total = 0;
        foreach ($users as $i => $user) {
            $team = Team::findOne([
                'user_id' => $user->id,
                'tournament_id' => $tournament->id,
            ]);
            if ($team === null) {
                continue;
            }

            $total++;
            echo "{$i} #{$user->id} {$user->first_name} {$user->last_name}\n";

            Message::send($user->chat_id, $message, $user);
        }

        echo "Отправлено сообщений: {$total}\n";
        return ExitCode::OK;
    }
}
