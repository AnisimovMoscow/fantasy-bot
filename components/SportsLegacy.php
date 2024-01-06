<?php

namespace app\components;

use app\helpers\Request;
use DOMDocument;

class SportsLegacy
{
    public const BIATHLON_ID = 225;
    public const TOURNAMENTS = [192, 193];

    private const MONTH = [
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

    // Возвращает команды пользователя
    public static function getUserTeams($id)
    {
        $json = Request::get("https://www.sports.ru/fantasy/tournaments.json?user_id={$id}");
        if ($json === null) {
            return [];
        }
        $data = json_decode($json);
        return $data;
    }

    // Возвращает дедлайн команды
    public static function getTeamDeadline($id)
    {
        $html = Request::get("https://www.sports.ru/fantasy/biathlon/team/points/{$id}.html");
        if ($html === null) {
            return null;
        }
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

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
                        $time = mktime($deadline[2], $deadline[3], 0, self::MONTH[$deadline[1]], $deadline[0], $year);
                        $now = time();
                        if ($time < $now) {
                            $year++;
                            $time = mktime($deadline[2], $deadline[3], 0, self::MONTH[$deadline[1]], $deadline[0], $year);
                        }
                        $deadline = date('Y-m-d H:i:s', $time);
                    } else {
                        $deadline = null;
                    }
                }
            }
        }

        return $deadline ?? null;
    }

    // Возвращает общее количество трансферов
    public static function getTeamTotalTransfers($id)
    {
        $html = Request::get("https://www.sports.ru/fantasy/biathlon/team/points/{$id}.html");
        if ($html === null) {
            return null;
        }
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            if ($table->getAttribute('class') != 'profile-table') {
                continue;
            }
            $trs = $table->getElementsByTagName('tr');
            foreach ($trs as $tr) {
                if ($tr->getElementsByTagName('th')->item(0)->nodeValue == 'Трансферы в туре') {
                    $transfers = $tr->getElementsByTagName('td')->item(0)->nodeValue;
                }
            }
        }

        return $transfers ?? null;
    }

    // Возвращает оставшиеся трансферы команды
    public static function getTeamTransfersLeft($id)
    {
        return self::getTeamTotalTransfers($id);
    }
}
