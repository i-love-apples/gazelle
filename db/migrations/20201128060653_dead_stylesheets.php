<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Clears out some old stylesheets. Will have to delete the `stylesheets` cache key after running this.
 */
final class DeadStylesheets extends AbstractMigration
{
    public function up(): void
    {
        $removedStylesheets = [
            'Hydro',
            'Anorex',
            'Mono',
            'Shiro',
            'Minimal',
            'Whatlove',
            'White.cd',
            'GTFO Spaceship',
            'Haze',
            'Apollo Mat',
        ];
        $this->query("DELETE FROM stylesheets WHERE `Name` IN ('" . implode("','", $removedStylesheets) . "')");
    }

    public function down(): void
    {
        $removedStylesheets = [
            'Hydro',
            'Anorex',
            'Mono',
            'Shiro',
            'Minimal',
            'Whatlove',
            'White.cd',
            'GTFO Spaceship',
            'Haze',
            'Apollo Mat',
        ];
        $this->query("DELETE FROM stylesheets WHERE `Name` IN ('" . implode("','", $removedStylesheets) . "')");
    }
}
