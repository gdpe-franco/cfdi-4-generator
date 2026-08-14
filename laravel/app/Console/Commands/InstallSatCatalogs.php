<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cfdi\V40\CatalogInstaller;
use Illuminate\Console\Command;
use RuntimeException;

class InstallSatCatalogs extends Command
{
    protected $signature = 'cfdi:catalogs:install {--update : Replace the existing resource from the configured pinned release}';

    protected $description = 'Install the pinned local SAT catalog resource';

    public function handle(CatalogInstaller $installer): int
    {
        try {
            $installed = $installer->install((bool) $this->option('update'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($installed ? 'SAT catalog installed.' : 'SAT catalog is already installed.');

        return self::SUCCESS;
    }
}
