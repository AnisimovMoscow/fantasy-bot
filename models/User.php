<?php

namespace app\models;

use yii\db\ActiveRecord;
use app\components\Html;

/**
 * Пользователи
 */
class User extends ActiveRecord
{
    public function rules()
    {
        return [
            [['chat_id', 'first_name', 'last_name', 'username', 'profile_url'], 'safe'],
        ];
    }
    
    public function getTeams()
    {
        return $this->hasMany(Team::className(), ['user_id' => 'id']);
    }
    
    /**
     * Обновляет список команд пользователя
     */
    public function updateTeams()
    {
        if (!empty($this->profile_url)) {
            // Запоминаем старые команды
            $oldTeams = [];
            foreach ($this->teams as $team) {
                $oldTeams[] = $team->url;
            }
            
            // Получаем список новых команд
            $dom = Html::load($this->profile_url.'fantasy/');
            $divs = $dom->getElementsByTagName('div');
            $url = parse_url($this->profile_url);
            $host = $url['scheme'].'://'.$url['host'];
            foreach ($divs as $div) {
                if ($div->getAttribute('class') == 'item user-league') {
                    $links  = $div->getElementsByTagName('a');
                    
                    $tournamentUrl = $host.$links->item(1)->getAttribute('href');
                    if (preg_match('/.*\/fantasy\/football\/.*/', $tournamentUrl)) {
                        // Проверяем турнир к которому относится команда
                        $tournament = Tournament::findOne(['url' => $tournamentUrl]);
                        if ($tournament === null) {
                            $tournament = new Tournament([
                                'url' => $tournamentUrl,
                                'name' => $links->item(1)->nodeValue,
                            ]);
                            $tournament->save();
                        }
                        
                        // Добавляем команду
                        $teamUrl = $host.$links->item(0)->getAttribute('href');
                        if (strpos($teamUrl, '/points') === false) {
                            $teamUrl = str_replace('/football/team/', '/football/team/points/', $teamUrl);
                        }
                        $team = Team::findOne(['url' => $teamUrl]);
                        if ($team === null) {
                            $team = new Team([
                                'user_id' => $this->id,
                                'url' => $teamUrl,
                                'name' => $this->removeEmoji($links->item(0)->nodeValue),
                                'tournament_id' => $tournament->id,
                            ]);
                            $team->save();
                        } else {
                            $index = array_search($teamUrl, $oldTeams);
                            array_splice($oldTeams, $index, 1);
                        }
                    }
                }
            }
            
            // Удаляем старые команды
            foreach ($oldTeams as $url) {
                $team = Team::findOne(['url' => $url]);
                if ($team !== null) {
                    $team->delete();
                }
            }
        }
    }
    
    /**
     * Удаляет эмодзи из названия команды
     */
    private function removeEmoji($text) {
        return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }
}
