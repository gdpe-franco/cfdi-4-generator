<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

use PhpCfdi\SatCatalogos\CFDI40\UsoCfdi;
use PhpCfdi\SatCatalogos\Factory;

class SatCatalog
{
    public function usoCfdi(string $id): UsoCfdi
    {
        return (new Factory)
            ->catalogosFromDsn('sqlite:'.Schema::satCatalogDatabase())
            ->usosCfdi40()
            ->obtain($id);
    }
}
