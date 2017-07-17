<?php

use yii\db\Migration;

class m170717_133227_add_user_site extends Migration
{
    public function up()
    {
        $this->addColumn('user', 'site', $this->string());
        User::updateAll(['site' => 'ru']);
    }
    
    public function down()
    {
        $this->dropColumn('user', 'site');
    }
}
