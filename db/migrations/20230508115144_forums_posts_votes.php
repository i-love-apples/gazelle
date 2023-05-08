<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

final class ForumsPostsVotes extends AbstractMigration
{
    public function up()
    {
        $this->table('forums_posts_votes', [
                'id' => false,
                'primary_key' => ['PostID', 'UserID'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8',
                'collation' => 'utf8_general_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('PostID', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
            ])
            ->addColumn('UserID', 'integer', [
                'null' => false,
                'limit' => '10',
                'signed' => false,
            ])
            ->addColumn('Vote', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_TINY,
            ])
            ->create();
    }

    public function down()
    {
        $this->table('forums_posts_votes')->drop()->update();
    }
}
