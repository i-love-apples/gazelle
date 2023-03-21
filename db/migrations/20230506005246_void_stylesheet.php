<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class VoidStylesheet extends AbstractMigration
{
    public function down() {
        $this->table('stylesheets')->insert([
            ['Name' => 'Void', 'Description' => 'Void by BIO']
        ])->save();
    }

    public function up()
    {
        $this->table('stylesheets')->insert([
            ['Name' => 'Void', 'Description' => 'Void by BIO']
        ])->save();
    }
}
