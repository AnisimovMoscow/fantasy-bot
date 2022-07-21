<?php

namespace app\helpers;

use Yii;

class Request
{
    const CONNECTTIMEOUT = 10;
    const TIMEOUT = 30;
    const USERAGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36';
    
    /**
     * Возвращает результат GET запроса
     */
    public static function get($url, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECTTIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $result = curl_exec($ch);
        if ($result === false) {
            Yii::info('Curl error. URL: ' . $url . ' Error: ' . curl_error($ch), 'curl');
        }
        curl_close($ch);
        return $result ?: null;
    }

    /**
     * Возвращает результат POST запроса
     */
    public static function post($url, $data, $format = 'form-data')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECTTIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_USERAGENT, self::USERAGENT);
        $query = '';
        if ($format == 'form-data') {
            $query = http_build_query($data);
        } elseif ($format == 'json') {
            $query = json_encode($data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($query),
            ]);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ?: null;
    }
}
