<?php
namespace app\models;

use yii\db\ActiveRecord;

class Tournament extends ActiveRecord
{
    public function rules()
    {
        return [
            [['webname', 'name', 'deadline', 'checked', 'transfers'], 'safe'],
        ];
    }

    public function getTeams()
    {
        return $this->hasMany(Team::class, ['tournament_id' => 'id']);
    }
}
