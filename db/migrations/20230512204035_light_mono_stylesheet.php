<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LightMonoStylesheet extends AbstractMigration
{
public function down() {
    $this->table('stylesheets')->insert([
        ['Name' => 'iAnon Light Mono', 'Description' => 'iAnon Light Mono by iAnon']
    ])->save();
}

public function up()
{
    $this->table('stylesheets')->insert([
        ['Name' => 'iAnon Light Mono', 'Description' => 'iAnon Light Mono by iAnon']
    ])->save();
}
}
