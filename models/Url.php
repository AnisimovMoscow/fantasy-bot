<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ссылки
 */
class Url extends ActiveRecord
{
    public function rules() {
        return [
            [['channel', 'url', 'publish_time'], 'safe'],
        ];
    }
    
    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $this->publish_time = date('Y-m-d H:i:s');
            return true;
        } else {
            return false;
        }
    }
}