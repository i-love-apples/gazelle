<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LightStylesheet extends AbstractMigration
{
public function down() {
    $this->table('stylesheets')->insert([
        ['Name' => 'iAnon Light', 'Description' => 'iAnon Light by iAnon']
    ])->save();
}

public function up()
{
    $this->table('stylesheets')->insert([
        ['Name' => 'iAnon Light', 'Description' => 'iAnon Light by iAnon']
    ])->save();
}
}
