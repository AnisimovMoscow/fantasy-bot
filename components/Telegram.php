<?php

namespace app\components;

use Yii;
use yii\base\Component;

class Telegram extends Component
{
    public static function validate($data)
    {
        $check_hash = $data['hash'];
        unset($data['hash']);

        $data_check_arr = [];
        foreach ($data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key = hash_hmac('sha256', Yii::$app->params['token'], 'WebAppData', true);

        $hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));
        return (strcmp($hash, $check_hash) === 0);
    }
}
