<?php

use yii\db\Migration;

class m220203_183241_add_user_timezone extends Migration
{
    public function up()
    {
        $this->addColumn('user', 'timezone', $this->string()->defaultValue('Europe/Moscow'));
    }

    public function down()
    {
        $this->dropColumn('user', 'timezone');
    }
}
