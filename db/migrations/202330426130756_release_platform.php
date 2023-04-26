<?php


use Phinx\Migration\AbstractMigration;

class ReleasePlatform extends AbstractMigration
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
    public function up()
    {
        $this->table('release_platform', ['id' => false, 'primary_key' => 'ID'])
                      ->addColumn('ID', 'integer', ['limit' => 10, 'identity' => true])
                      ->addColumn('Name', 'string', ['limit' => 50])
                      ->addIndex(['Name'], ['unique' => true])
                      ->create();
        $data = [
            ['ID' =>  1, 'Name' => 'Power PC'],
            ['ID' =>  2, 'Name' => 'Intel'],
            ['ID' =>  3, 'Name' => 'UB'],
            ['ID' =>  4, 'Name' => 'U2B'],
            ['ID' =>  5, 'Name' => 'ARM'],
            ['ID' =>  6, 'Name' => 'Unofficial CrossOver (games only)'],
            ['ID' =>  7, 'Name' => 'Unofficial Cider (games only)'],
            ['ID' =>  8, 'Name' => 'Unofficial Wineskin (games only)'],
            ['ID' =>  9, 'Name' => 'Unofficial DosBox (games only)']
        ];

        $this->table('release_platform')->insert($data)->update();
    }

    public function down()
    {
        $this->table('release_platform')->drop()->update();
    }
}
