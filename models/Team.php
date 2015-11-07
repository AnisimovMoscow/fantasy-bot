<?php
namespace app\models;

use yii\db\ActiveRecord;

class Team extends ActiveRecord
{
    public function rules() {
        return [
            [['user_id', 'url', 'name', 'tournament_id'], 'safe'],
        ];
    }
	
	public function getTournament() {
        return $this->hasOne(Tournament::className(), ['id' => 'tournament_id']);
    }
}