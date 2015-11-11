<?php
namespace app\models;

use yii\db\ActiveRecord;
use DOMDocument;

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
    
    public function getTransfers() {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTMLFile($this->url);
        libxml_clear_errors();
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