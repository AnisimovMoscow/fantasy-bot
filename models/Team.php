<?php
namespace app\models;

use yii\db\ActiveRecord;

class Team extends ActiveRecord
{
    public function rules()
    {
        return [
            [['user_id', 'sports_id', 'name', 'tournament_id'], 'safe'],
        ];
    }

    public function getTournament()
    {
        return $this->hasOne(Tournament::class, ['id' => 'tournament_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    // Возвращает количество оставшихся трансферов
    public function getTransfers()
    {
        // TODO
        return 0;
    }
}
