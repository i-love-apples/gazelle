<?php

use Phinx\Seed\AbstractSeed;

class BonusDiscount extends AbstractSeed
{
    public function run()
    {
        $this->table('site_options')->insert([
            'Name'    => 'bonus-discount',
            'Value'   => 0,
            'Comment' => 'Bonus store discount (0 = no discount, 100 = everything free)',
        ])->save();

        $this->table('site_options')->insert([
            'Name'    => 'freeleech-min',
            'Value'   => -1,
            'Comment' => 'Minimum torrent MB for automatic freeleech on upload ( -1 disabled )',
        ])->save();
    }
}
