<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPersonalDetailsToUsers extends BaseMigration
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
            ->addColumn('address', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'bio'
            ])
            ->addColumn('relationship_status', 'string', [
                'limit' => 20,
                'null' => true,
                'after' => 'address',
                'comment' => 'Values: single, taken, married'
            ])
            ->addColumn('contact_links', 'text', [
                'null' => true,
                'after' => 'relationship_status',
                'comment' => 'JSON array of social media links'
            ])
            ->update();
    }
}
