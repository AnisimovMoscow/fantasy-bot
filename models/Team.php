<?php
namespace app\models;

use yii\db\ActiveRecord;
use app\components\Html;

/**
 * Команды
 */
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
    
    public function getUser() {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
    
    /**
     * Возвращает количество оставшихся трансферов
     */
    public function getTransfers() {
        $dom = Html::load($this->url);
        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            if ($table->getAttribute('class') == 'profile-table') {
                $trs = $table->getElementsByTagName('tr');
                foreach ($trs as $tr) {
                    if ($tr->getElementsByTagName('th')->item(0)->nodeValue == 'Трансферы в туре') {
                        $transfers = $tr->getElementsByTagName('td')->item(0)->nodeValue;
                    }
                }
            }
        }
        return (isset($transfers))? $transfers : 0;
    }
}