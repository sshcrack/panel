<?php

namespace Kriegerhost\Console\Commands\Overrides;

use Kriegerhost\Console\RequiresDatabaseMigrations;
use Illuminate\Database\Console\Seeds\SeedCommand as BaseSeedCommand;

class SeedCommand extends BaseSeedCommand
{
    use RequiresDatabaseMigrations;

    /**
     * Block someone from running this seed command if they have not completed
     * the migration process.
     */
    public function handle()
    {
        if (!$this->hasCompletedMigrations()) {
            $this->showMigrationWarning();

            return;
        }

        parent::handle();
    }
}
