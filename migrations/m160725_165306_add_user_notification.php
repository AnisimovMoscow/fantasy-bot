<?php

use yii\db\Migration;
use app\models\User;

class m160725_165306_add_user_notification extends Migration
{
    public function up()
    {
        $this->addColumn('user', 'notification', $this->boolean());
        User::updateAll(['notification' => true]);
    }
    
    public function down()
    {
        $this->dropColumn('user', 'notification');
    }
}
