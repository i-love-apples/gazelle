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
            'Anorex',
            'Apollo Mat',
            'Dark Ambient',
            'GTFO Spaceship',
            'Haze',
            'Hydro',
            'Kuro',
            'Layer cake',
            'LinoHaze',
            'Minimal',
            'Mono',
            'Post Office',
            'Proton',
            'Shiro',
            'Whatlove',
            'White.cd',
            'Xanax cake',
            'postmod',
        ];
        $this->query("DELETE FROM stylesheets WHERE `Name` IN ('" . implode("','", $removedStylesheets) . "')");
    }

    public function down(): void
    {
        $removedStylesheets = [
            'Anorex',
            'Apollo Mat',
            'Dark Ambient',
            'GTFO Spaceship',
            'Haze',
            'Hydro',
            'Kuro',
            'Layer cake',
            'LinoHaze',
            'Minimal',
            'Mono',
            'Post Office',
            'Proton',
            'Shiro',
            'Whatlove',
            'White.cd',
            'Xanax cake',
            'postmod',
        ];
        $this->query("DELETE FROM stylesheets WHERE `Name` IN ('" . implode("','", $removedStylesheets) . "')");
    }
}
