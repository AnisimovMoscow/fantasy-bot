<?php

use yii\db\Migration;

class m220203_201842_fix_user_chat_id extends Migration
{
    public function up()
    {
        $this->alterColumn('user', 'chat_id', $this->bigInteger());
    }

    public function down()
    {
        $this->alterColumn('user', 'chat_id', $this->integer());
    }
}
