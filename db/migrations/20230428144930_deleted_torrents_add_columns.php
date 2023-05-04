<?php


use Phinx\Migration\AbstractMigration;

class DeletedTorrentsAddColumns extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $this->table('deleted_torrents')
             ->addColumn('Version',             'string', ['length' => 45, 'default' => ''])
             ->addColumn('Platform',            'string', ['length' => 45, 'default' => ''])
             ->addColumn('Includes',            'string', ['length' => 45, 'default' => ''])
             ->addColumn('OSVersion',           'string', ['length' => 100, 'default' => ''])
             ->addColumn('Processor',           'string', ['length' => 100, 'default' => ''])
             ->addColumn('RAM',                 'string', ['length' => 100, 'default' => ''])
             ->addColumn('VRAM',                'string', ['length' => 100, 'default' => ''])
             ->update();
    }
}
