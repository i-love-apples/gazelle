<?php


use Phinx\Migration\AbstractMigration;

class SphinxTAddColumns extends AbstractMigration
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
        $this->table('sphinx_t')
             ->addColumn('version',             'string', ['length' => 45, 'default' => ''])
             ->addColumn('platform',            'string', ['length' => 45, 'default' => ''])
             ->addColumn('includes',            'string', ['length' => 45, 'default' => ''])
             ->addColumn('osversion',           'string', ['length' => 100, 'default' => ''])
             ->addColumn('processor',           'string', ['length' => 100, 'default' => ''])
             ->addColumn('ram',                 'string', ['length' => 100, 'default' => ''])
             ->addColumn('vram',                'string', ['length' => 100, 'default' => ''])
             ->update();
    }
}
