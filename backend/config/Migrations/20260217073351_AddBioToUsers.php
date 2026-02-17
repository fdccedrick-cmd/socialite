<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddBioToUsers extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('bio', 'text', [
            'default' => null,
            'null' => true,
            'comment' => 'User biography or profile description',
        ])
        ->update();
    }
}
