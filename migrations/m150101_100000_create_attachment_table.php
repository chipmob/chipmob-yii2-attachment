<?php

use yii\db\Migration;

class m150101_100000_create_attachment_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName == 'mysql' ? 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB' : null;

        $this->createTable('{{%attachment}}', [
            'id' => $this->primaryKey(),
            'model' => $this->string(255)->notNull()->comment('CLASS связанной модели'),
            'item_id' => $this->integer()->notNull()->comment('ID связанной модели'),
            'name' => $this->string(255)->notNull()->comment('Имя загружаемого файла'),
            'type' => $this->string(255)->notNull()->comment('Расширение файла'),
            'hash' => $this->char(40)->notNull()->comment('Имя сохраненного файла'),
            'mime' => $this->string(255)->notNull()->comment('MIME тип файла'),
            'size' => $this->integer()->notNull()->comment('Размер файла'),
            'created_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
        ], $tableOptions);
        $this->addCommentOnTable('{{%attachment}}', 'Прикрепленные файлы');

        $this->createIndex('index_model', '{{%attachment}}', 'model');
        $this->createIndex('index_item_id', '{{%attachment}}', 'item_id');
        $this->createIndex('index_created_at', '{{%attachment}}', 'created_at');
        $this->createIndex('index_created_by', '{{%attachment}}', 'created_by');
    }

    public function safeDown()
    {
        $this->dropTable('{{%attachment}}');
    }
}
