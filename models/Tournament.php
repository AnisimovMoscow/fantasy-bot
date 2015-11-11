<?php
namespace app\models;

use yii\db\ActiveRecord;

class Tournament extends ActiveRecord
{
    public function rules() {
        return [
            [['url', 'name', 'deadline', 'checked', 'transfers'], 'safe'],
        ];
    }
    
    public function getTeams() {
        return $this->hasMany(Team::className(), ['tournament_id' => 'id']);
    }
}