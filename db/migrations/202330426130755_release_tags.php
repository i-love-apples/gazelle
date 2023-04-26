<?php


use Phinx\Migration\AbstractMigration;

class ReleaseTags extends AbstractMigration
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
        $this->table('release_tags', ['id' => false, 'primary_key' => 'ID'])
                      ->addColumn('ID', 'integer', ['limit' => 10, 'identity' => true])
                      ->addColumn('Name', 'string', ['limit' => 50])
                      ->addIndex(['Name'], ['unique' => true])
                      ->create();
        $data = [
            ['ID' =>  1, 'Name' => 'action'],
            ['ID' =>  2, 'Name' => 'adventure'],
            ['ID' =>  3, 'Name' => 'audio'],
            ['ID' =>  4, 'Name' => 'audiobook'],
            ['ID' =>  5, 'Name' => 'business'],
            ['ID' =>  6, 'Name' => 'clipart'],
            ['ID' =>  7, 'Name' => 'development'],
            ['ID' =>  8, 'Name' => 'ebook'],
            ['ID' =>  9, 'Name' => 'effect'],
            ['ID' => 10, 'Name' => 'font'],
            ['ID' => 11, 'Name' => 'gog'],
            ['ID' => 12, 'Name' => 'graphics'],
            ['ID' => 13, 'Name' => 'icon'],
            ['ID' => 14, 'Name' => 'itunes.lp'],
            ['ID' => 15, 'Name' => 'learning'],
            ['ID' => 16, 'Name' => 'movie'],
            ['ID' => 17, 'Name' => 'music'],
            ['ID' => 18, 'Name' => 'music.audio'],
            ['ID' => 19, 'Name' => 'music.score'],
            ['ID' => 20, 'Name' => 'os.classic'],
            ['ID' => 21, 'Name' => 'os.ios'],
            ['ID' => 22, 'Name' => 'os.mac'],
            ['ID' => 23, 'Name' => 'plugins'],
            ['ID' => 24, 'Name' => 'puzzle'],
            ['ID' => 25, 'Name' => 'roleplay'],
            ['ID' => 26, 'Name' => 'sports'],
            ['ID' => 27, 'Name' => 'stock.photos'],
            ['ID' => 28, 'Name' => 'stock.videos'],
            ['ID' => 29, 'Name' => 'strategy'],
            ['ID' => 30, 'Name' => 'tutorials'],
            ['ID' => 31, 'Name' => 'tvshows'],
            ['ID' => 32, 'Name' => 'utilities'],
            ['ID' => 33, 'Name' => 'video'],
            ['ID' => 34, 'Name' => 'web'],
            ['ID' => 35, 'Name' => 'website.templates']
        ];

        $this->table('release_tags')->insert($data)->update();
    }

    public function down()
    {
        $this->table('release_tags')->drop()->update();
    }
}
