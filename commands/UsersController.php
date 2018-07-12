<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use TelegramBot\Api\BotApi;
use app\models\User;
use app\models\Tournament;
use app\models\Team;
use Exception;

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
                    $message .= "\n- ".$team->name.' ('.$team->tournament->name.')';
                }
            } else {
                $message = 'Нет команд';
            }
            echo $message."\n";
        }
    }
    
    /**
     * Обновляет список команд у всех пользователей
     */
    public function actionUpdateAll() {
        $users = User::find()->where(['!=', 'profile_url', ''])->all();
        foreach ($users as $i => $user) {
            echo $i.' '.$user->id.' '.$user->first_name.' '.$user->last_name."\n";
            $user->updateTeams();
        }
    }
    
    /**
     * Проверяет замены у Avengers
     */
    public function actionAvengers() {
        $time = time() + 30*60;
        
        $tournaments = Tournament::find()
            ->where(['>', 'deadline', date('Y-m-d H:i:s')])
            ->andWhere(['<', 'deadline', date('Y-m-d H:i:s', $time)])
            ->andWhere(['like', 'url', 'https://www.sports.ru/'])
            ->all();
        
        $cache = Yii::$app->cache;
        
        foreach (['avengers', 'moneyball'] as $teamName) {
            $params = Yii::$app->params[$teamName];
            foreach ($tournaments as $tournament) {
                $key = $teamName.'_check_'.$tournament->id;
                $check = $cache->get($key);
                if (!$check) {
                    $notChanges = [];
                    foreach ($params['users'] as $id => $name) {
                        $profileUrl = 'https://www.sports.ru/profile/'.$id.'/';
                        $user = User::findOne(['profile_url' => $profileUrl]);
                        if ($user === null) {
                            echo $name." - user not found\n";
                            continue;
                        }

                        $team = Team::findOne(['user_id' => $user->id, 'tournament_id' => $tournament->id]);
                        if ($team === null) {
                            echo $name." - user not found\n";
                            continue;
                        }

                        $transfers = $team->getTransfers();
                        if ($transfers == $tournament->transfers) {
                            $notChanges[] = $name;
                        }
                    }

                    $message = $tournament->name.': ';
                    if (empty($notChanges)) {
                        $message .= 'все сделали замены';
                    } elseif (count($notChanges) == 1) {
                        $message .= $notChanges[0].' не сделал замены';
                    } else {
                        $message .= implode(', ', $notChanges).' не сделали замены';
                    }

                    try {
                        $bot = new BotApi($params['token']);
                        $bot->sendMessage($params['chat_id'], $message);
                    } catch (Exception $e) {
                        Yii::info($e->getMessage(), 'send');
                    }

                    $cache->set($key, true, 60*60);
                }
            }
        }
    }
}
