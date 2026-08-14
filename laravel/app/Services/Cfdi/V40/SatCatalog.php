<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

use InvalidArgumentException;
use PhpCfdi\SatCatalogos\CFDI40\UsoCfdi;
use PhpCfdi\SatCatalogos\Exceptions\SatCatalogosNotFoundException;
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

    public function usoCfdiAllowsRegimenFiscalReceiver(string $usoCfdi, string $regimenFiscal): bool
    {
        try {
            $entry = $this->usoCfdi($usoCfdi);
        } catch (SatCatalogosNotFoundException) {
            throw new InvalidArgumentException("receptor.usoCFDI \"{$usoCfdi}\" is not a known CFDI 4.0 catalog value.");
        }

        return in_array($regimenFiscal, $entry->regimenesFiscalesReceptoresList(), true);
    }
}
