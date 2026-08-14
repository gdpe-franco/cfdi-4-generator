<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

use InvalidArgumentException;

class IngresoBusinessValidator
{
    private const FOREIGN_GENERIC_RFC = 'XEXX010101000';

    public function __construct(private SatCatalog $catalog) {}

    public function validate(array $input): void
    {
        $comprobante = $input['comprobante'];
        $receptor = $input['receptor'];

        if ($comprobante['formaPago'] === '99' && $comprobante['metodoPago'] !== 'PPD') {
            throw new InvalidArgumentException('comprobante.formaPago 99 requires comprobante.metodoPago PPD.');
        }
        if ($comprobante['metodoPago'] === 'PPD' && $comprobante['formaPago'] !== '99') {
            throw new InvalidArgumentException('comprobante.metodoPago PPD requires comprobante.formaPago 99.');
        }
        if ($comprobante['exportacion'] === '02') {
            throw new InvalidArgumentException('comprobante.exportacion 02 requires the Comercio Exterior complement, which is unsupported.');
        }
        if ($receptor['rfc'] === self::FOREIGN_GENERIC_RFC
            && ($receptor['regimenFiscalReceptor'] !== '616' || $receptor['usoCFDI'] !== 'S01')) {
            throw new InvalidArgumentException('receptor.rfc XEXX010101000 requires receptor.regimenFiscalReceptor 616 and receptor.usoCFDI S01.');
        }
        if (! $this->catalog->usoCfdiAllowsRegimenFiscalReceiver($receptor['usoCFDI'], $receptor['regimenFiscalReceptor'])) {
            throw new InvalidArgumentException("receptor.usoCFDI \"{$receptor['usoCFDI']}\" is incompatible with receptor.regimenFiscalReceptor \"{$receptor['regimenFiscalReceptor']}\".");
        }
    }
}
