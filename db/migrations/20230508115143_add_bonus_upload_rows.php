<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBonusUploadRows extends AbstractMigration
{
public function down() {
    $this->table('bonus_item')->insert([
        ['Price' => 1000, 'Amount' => 1, 'MinClass' => 0, 'FreeClass' => 999999, 'Label' => 'upload-1', 'Title' => 'Buy 1GB Upload', 'sequence' => 15],
        ['Price' => 10000, 'Amount' => 10, 'MinClass' => 0, 'FreeClass' => 999999, 'Label' => 'upload-2', 'Title' => 'Buy 10GB Upload', 'sequence' => 16],
        ['Price' => 100000, 'Amount' => 100, 'MinClass' => 0, 'FreeClass' => 999999, 'Label' => 'upload-3', 'Title' => 'Buy 100GB Upload', 'sequence' => 17],
        ['Price' => 1000000, 'Amount' => 1000, 'MinClass' => 0, 'FreeClass' => 999999, 'Label' => 'upload-4', 'Title' => 'Buy 1TB Upload', 'sequence' => 18]
    ])->save();
}

public function up()
{
    $this->table('bonus_item')->insert([
        ['Price' => 1000, 'Amount' => 1, 'MinClass' => 0, 'FreeClass' => 999999, 'Label' => 'upload-1', 'Title' => 'Buy 1GB Upload', 'sequence' => 15],
        ['Price' => 10000, 'Amount' => 10, 'MinClass' => 0, 'FreeClass' => 999999, 'Label' => 'upload-2', 'Title' => 'Buy 10GB Upload', 'sequence' => 16],
        ['Price' => 100000, 'Amount' => 100, 'MinClass' => 0, 'FreeClass' => 999999, 'Label' => 'upload-3', 'Title' => 'Buy 100GB Upload', 'sequence' => 17],
        ['Price' => 1000000, 'Amount' => 1000, 'MinClass' => 0, 'FreeClass' => 999999, 'Label' => 'upload-4', 'Title' => 'Buy 1TB Upload', 'sequence' => 18]
    ])->save();
}
}
