<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Clears out some old stylesheets. Will have to delete the `stylesheets` cache key after running this.
 */
final class DeleteStyles extends AbstractMigration
{
    public function up(): void
    {
        $removedStylesheets = [
            'Layer cake',
            'Proton',
            'postmod',
            'Kuro',
            'Dark Ambient',
            'Xanax cake',
            'Haze',
            'Post Office',
            'LinoHaze',
            'Apollo Mat',
        ];
        $this->query("DELETE FROM stylesheets WHERE `Name` IN ('" . implode("','", $removedStylesheets) . "')");

        $row = $this->query("SELECT * FROM stylesheets WHERE `Name` = 'Dark Cake'")->fetch(\PDO::FETCH_NUM);
        if ($row === false) {
            $this->table('stylesheets')->insert([
                ['Name' => 'Dark Cake', 'Description' => 'Dark Cake for Orpheus']
            ])->save();
        }
    }

    public function down(): void
    {
        $this->query("DELETE FROM stylesheets WHERE `Name` = 'Dark Cake'");
    }
}
