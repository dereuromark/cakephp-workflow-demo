<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateReleases extends BaseMigration
{
    public function change(): void
    {
        $this->table('releases')
            ->addColumn('version', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('state', 'string', ['limit' => 64, 'null' => false, 'default' => 'draft'])
            ->addColumn('check_attempts', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('canary_attempts', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('fixed', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('state_changed_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->create();
    }
}
