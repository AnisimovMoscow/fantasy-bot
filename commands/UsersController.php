<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\User;

/**
 * Команды для работы с пользователем
 */
class UsersController extends Controller
{
    /**
     * Обновление списка команд
     */
    public function actionUpdate($id) {
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
        foreach ($users as $user) {
            $user->updateTeams();
        }
    }
}