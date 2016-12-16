<?php
namespace app\models;

use yii\db\ActiveRecord;
use app\components\Html;

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
            // Запоминаем старые команды
            $oldTeams = [];
            foreach ($this->teams as $team) {
                $oldTeams[] = $team->url;
            }
            
            // Получаем список новых команд
            $dom = Html::load($this->profile_url.'fantasy/');
            $divs = $dom->getElementsByTagName('div');
            $host = 'https://www.sports.ru';
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
                        $teamUrl = str_replace('/football/team/', '/football/team/points/', $teamUrl);
                        $team = Team::findOne(['url' => $teamUrl]);
                        if ($team === null) {
                            $team = new Team([
                                'user_id' => $this->id,
                                'url' => $teamUrl,
                                'name' => $links->item(0)->nodeValue,
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
}