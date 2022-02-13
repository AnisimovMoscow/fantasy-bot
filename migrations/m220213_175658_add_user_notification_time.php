<?php

use yii\db\Migration;

class m220213_175658_add_user_notification_time extends Migration
{
    public function up()
    {
        $this->addColumn('user', 'notification_time', $this->time()->defaultValue('11:00'));
    }

    public function down()
    {
        $this->dropColumn('user', 'notification_time');
    }
}
