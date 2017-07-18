<?php

use yii\db\Migration;

class m170718_055425_delete_table_url extends Migration
{
    public function up()
    {
        $this->dropTable('url');
    }
    
    public function down()
    {
        $this->createTable('url', [
            'id' => $this->primaryKey(),
            'channel' => $this->string(),
            'url' => $this->string(),
            'publish_time' => $this->dateTime(),
        ]);
        $this->createIndex('channel', 'url', 'channel');
    }
}
