<?php

use Phinx\Migration\AbstractMigration;

class ForumsPostsAddColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('forums_posts')
             ->addColumn('Votes', 'integer', ['default' => 0])
             ->update();
    }
}
