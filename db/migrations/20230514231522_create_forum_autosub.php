<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateForumAutosub extends AbstractMigration
{
    public function up()
    {
        $this->table('forum_autosub', [
                'id' => false,
                'primary_key' => ['id_forum'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8',
                'collation' => 'utf8_general_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('id_forum', 'integer', [
                'null' => false
            ])
            ->addColumn('id_user', 'integer', [
                'null' => false
            ])
            ->addColumn('created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['id_forum'], [
                'name' => 'id_forum',
                'unique' => false,
            ])
            ->addIndex(['id_user'], [
                'name' => 'id_user',
                'unique' => false,
            ])
            ->create();
    }

    public function down() {
        $this->table('forum_autosub')->drop()->update();
    }
}
