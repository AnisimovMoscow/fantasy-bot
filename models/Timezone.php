<?php

namespace app\models;

use DateTime;
use DateTimeZone;
use yii\base\Model;

class Timezone extends Model
{
    public static function getAll()
    {
        $all = DateTimeZone::listIdentifiers();
        $timezones = [];
        foreach ($all as $name) {
            $timezone = self::parseName($name);
            if (!array_key_exists($timezone['group'], $timezones)) {
                $timezones[$timezone['group']] = [];
            }
            
            $date = new DateTime('now', new DateTimeZone($name));
            $timezones[$timezone['group']][] = [
                'name' => $name,
                'location' => $timezone['location'] . ' (' . $date->format('H:i') . ')',
            ];
        }

        return $timezones;
    }

    public static function parseName($name)
    {
        if (preg_match('/(\w+)\/(.+)/', $name, $matches)) {
            return [
                'group' => $matches[1],
                'location' => $matches[2],
            ];
        } else {
            return [
                'group' => 'Other',
                'location' => $name,
            ];
        }
    }
}
