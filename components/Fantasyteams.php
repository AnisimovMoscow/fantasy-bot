<?php

namespace app\components;

use app\helpers\Request;

class Fantasyteams
{
    /**
     * Возвращает команду
     * 
     * @param string $slug слаг команды
     * @return array Команда
     */
    public static function getTeam($slug) 
    {
        $url = 'https://fantasyteams.ru/teams/info?slug=' . $slug;
        $json = Request::get($url);
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        return $data;
    }
}
