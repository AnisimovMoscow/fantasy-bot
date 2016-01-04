<?php

use yii\db\Migration;

class m160104_133133_create_table_url extends Migration
{
    public function up() {
        $this->createTable('url', [
            'id' => $this->primaryKey(),
            'channel' => $this->string(),
            'url' => $this->string(),
            'publish_time' => $this->dateTime(),
        ]);
        $this->createIndex('channel', 'url', 'channel');
    }
    
    public function down() {
        $this->dropTable('url');
    }
}