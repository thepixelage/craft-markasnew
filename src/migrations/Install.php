<?php

namespace thepixelage\markasnew\migrations;

use craft\db\Migration;
use thepixelage\markasnew\db\Table;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown(): bool
    {
        $this->deleteTables();

        return true;
    }

    private function createTables()
    {
        $this->createTable(Table::MARKASNEW_ELEMENTS, [
            'id' => $this->integer()->notNull(),
            'markNewUntilDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);
    }

    private function createIndexes()
    {
        $this->createIndex(null, Table::MARKASNEW_ELEMENTS, ['markNewUntilDate'], false);
    }

    private function addForeignKeys()
    {
        $this->addForeignKey(null, Table::MARKASNEW_ELEMENTS, ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
    }

    private function deleteTables()
    {
        $this->dropTableIfExists(Table::MARKASNEW_ELEMENTS);
    }
}
