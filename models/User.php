<?php
namespace app\models;

use yii\db\ActiveRecord;
use DOMDocument;

/**
 * Пользователи
 */
class User extends ActiveRecord
{
    public function rules() {
        return [
            [['chat_id', 'first_name', 'last_name', 'username', 'profile_url'], 'safe'],
        ];
    }
    
    public function getTeams() {
        return $this->hasMany(Team::className(), ['user_id' => 'id']);
    }
    
    /**
     * Обновляет список команд пользователя
     */
    public function updateTeams() {
        if (!empty($this->profile_url)) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTMLFile($this->profile_url.'fantasy/');
            libxml_clear_errors();
            $divs = $dom->getElementsByTagName('div');
            $host = 'http://www.sports.ru';
            foreach ($divs as $div) {
                if ($div->getAttribute('class') == 'item user-league') {
                    $links  = $div->getElementsByTagName('a');

                    $tournamentUrl = $host.$links->item(1)->getAttribute('href');
                    if (preg_match('/.*\/fantasy\/football\/.*/', $tournamentUrl)) {
                        $tournament = Tournament::findOne(['url' => $tournamentUrl]);
                        if ($tournament === null) {
                            $tournament = new Tournament([
                                'url' => $tournamentUrl,
                                'name' => $links->item(1)->nodeValue,
                            ]);
                            $tournament->save();
                        }

                        $teamUrl = $host.$links->item(0)->getAttribute('href');
                        $team = Team::findOne(['url' => $teamUrl]);
                        if ($team === null) {
                            $team = new Team([
                                'user_id' => $this->id,
                                'url' => $teamUrl,
                                'name' => $links->item(0)->nodeValue,
                                'tournament_id' => $tournament->id,
                            ]);
                            $team->save();
                        }
                    }
                }
            }
        }
    }
}