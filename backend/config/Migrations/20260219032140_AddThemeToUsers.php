<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddThemeToUsers extends BaseMigration
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
        $table
            ->addColumn('theme', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => 'dark',
                'after' => 'bio',
            ])
            ->update();
    }
}
