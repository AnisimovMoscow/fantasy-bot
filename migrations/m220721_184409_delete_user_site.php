<?php

use app\models\Team;
use app\models\User;
use yii\db\Migration;

class m220721_184409_delete_user_site extends Migration
{
    public function up()
    {
        User::deleteAll(['site' => 'ua']);
        User::deleteAll(['site' => 'by']);
        $this->dropColumn('user', 'site');

        $users = User::find()->all();
        foreach ($users as $user) {
            preg_match('/sports\.ru\/profile\/(\d+)/', $user->profile_url, $matches);
            if (!empty($matches[1])) {
                $user->profile_url = $matches[1];
                $user->save();
            }
        }
        $this->renameColumn('user', 'profile_url', 'sports_id');

        $this->renameColumn('tournament', 'url', 'webname');

        $teams = Team::find()->all();
        foreach ($teams as $team) {
            preg_match('/sports\.ru\/fantasy\/football\/team\/points\/(\d+)\.html/', $team->url, $matches);
            if (!empty($matches[1])) {
                $team->url = $matches[1];
                $team->save();
            }
        }
        $this->renameColumn('team', 'url', 'sports_id');
    }

    public function down()
    {
        $this->addColumn('user', 'site', $this->string());
        User::updateAll(['site' => 'ru']);

        $this->renameColumn('user', 'sports_id', 'profile_url');

        $this->renameColumn('tournament', 'webname', 'url');

        $this->renameColumn('team', 'sports_id', 'url');
    }
}
