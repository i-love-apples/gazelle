<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DarkStylesheet extends AbstractMigration
{
public function down() {
    $this->table('stylesheets')->insert([
        ['Name' => 'Dark', 'Description' => 'Dark by BIO']
    ])->save();
}

public function up()
{
    $this->table('stylesheets')->insert([
        ['Name' => 'Dark', 'Description' => 'Dark by BIO']
    ])->save();
}
}
