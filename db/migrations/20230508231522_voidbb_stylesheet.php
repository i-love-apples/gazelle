<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class VoidBBStylesheet extends AbstractMigration
{
public function down() {
    $this->table('stylesheets')->insert([
        ['Name' => 'VoidBB', 'Description' => 'VoidBB by BIO']
    ])->save();
}

public function up()
{
    $this->table('stylesheets')->insert([
        ['Name' => 'VoidBB', 'Description' => 'VoidBB by BIO']
    ])->save();
}
}
