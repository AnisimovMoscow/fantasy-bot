<?php
namespace app\models;

use yii\db\ActiveRecord;

class Tournament extends ActiveRecord
{
    public function rules() {
        return [
            [['url', 'name'], 'safe'],
        ];
    }
}