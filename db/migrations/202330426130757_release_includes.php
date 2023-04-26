<?php


use Phinx\Migration\AbstractMigration;

class ReleaseIncludes extends AbstractMigration
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
        $this->table('release_includes', ['id' => false, 'primary_key' => 'ID'])
                      ->addColumn('ID', 'integer', ['limit' => 10, 'identity' => true])
                      ->addColumn('Name', 'string', ['limit' => 50])
                      ->addIndex(['Name'], ['unique' => true])
                      ->create();
        $data = [
            ['ID' =>  1, 'Name' => "Pre-K'ed"],
            ['ID' =>  2, 'Name' => 'K'],
            ['ID' =>  3, 'Name' => 'Serial'],
            ['ID' =>  4, 'Name' => 'KG'],
            ['ID' =>  5, 'Name' => 'GOG'],
            ['ID' =>  6, 'Name' => 'DRM-Free']
        ];

        $this->table('release_includes')->insert($data)->update();
    }

    public function down()
    {
        $this->table('release_includes')->drop()->update();
    }
}
