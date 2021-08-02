<?php
namespace app\components;

use Yii;
use yii\base\BaseObject;
use DOMDocument;

/**
 * Работа с html
 */
class Html extends BaseObject
{
    /**
     * Загружает страницу по url
     * @param string $url адрес страницы
     * @return DOMDocument DOM-дерево
     */
    public static function load($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.86 Safari/537.36');

        $retry = 0;
        while ($retry < 3) {
            $html = curl_exec($ch);
            if ($html === false) {
                Yii::info('Curl error. URL: ' . $url . ' Error: ' . curl_error($ch) . ' Retry: ' . $retry, 'curl');
            }
            $retry++;
        }
        curl_close($ch);
        
        $dom = new DOMDocument();
        if (!empty($html)) {
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();
        }
        
        return $dom;
    }
}
