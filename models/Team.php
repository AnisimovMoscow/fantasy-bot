<?php
namespace app\models;

use yii\db\ActiveRecord;
use app\components\Html;

/**
 * Команды
 */
class Team extends ActiveRecord
{
    public function rules()
    {
        return [
            [['user_id', 'url', 'name', 'tournament_id'], 'safe'],
        ];
    }
    
    public function getTournament()
    {
        return $this->hasOne(Tournament::className(), ['id' => 'tournament_id']);
    }
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
    
    /**
     * Возвращает количество оставшихся трансферов
     */
    public function getTransfers()
    {
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

    /**
     * Возвращает дедлайн
     */
    public function getDeadline()
    {
        $month = [
            'января' => 1,
            'февраля' => 2,
            'марта' => 3,
            'апреля' => 4,
            'мая' => 5,
            'июня' => 6,
            'июля' => 7,
            'августа' => 8,
            'сентября' => 9,
            'октября' => 10,
            'ноября' => 11,
            'декабря' => 12,
        ];

        $dom = Html::load($this->url);
        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            if ($table->getAttribute('class') != 'profile-table') {
                continue;
            }
            $trs = $table->getElementsByTagName('tr');
            foreach ($trs as $tr) {
                if ($tr->getElementsByTagName('th')->item(0)->nodeValue == 'Дедлайн') {
                    $deadline = $tr->getElementsByTagName('td')->item(0)->nodeValue;
                    $deadline = str_replace(['|', ':'], ' ', $deadline);
                    $deadline = explode(' ', $deadline);
                    if (count($deadline) == 4) {
                        $year = date('Y');
                        $time = mktime($deadline[2], $deadline[3], 0, $month[$deadline[1]], $deadline[0], $year);
                        $now = time();
                        if ($time < $now) {
                            $year++;
                            $time = mktime($deadline[2], $deadline[3], 0, $month[$deadline[1]], $deadline[0], $year);
                        }
                        $deadline = date('Y-m-d H:i:s', $time);
                    } else {
                        $deadline = null;
                    }
                } elseif ($tr->getElementsByTagName('th')->item(0)->nodeValue == 'Трансферы в туре') {
                    $transfers = $tr->getElementsByTagName('td')->item(0)->nodeValue;
                }
            }
        }

        return [
            'deadline' => $deadline ?? null,
            'transfers' => $transfers ?? null,
        ];
    }
}
