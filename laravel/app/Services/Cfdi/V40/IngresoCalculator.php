<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

class IngresoCalculator
{
    public function calculate(array $input): array
    {
        $subtotal = '0.000000';
        $totalTransferredTaxes = '0.000000';
        $concepts = [];

        foreach ($input['conceptos'] as $concepto) {
            $amount = bcmul($concepto['cantidad'], $concepto['valorUnitario'], 6);
            $tax = bcmul($amount, $concepto['iva'], 6);
            $subtotal = bcadd($subtotal, $amount, 6);
            $totalTransferredTaxes = bcadd($totalTransferredTaxes, $tax, 6);
            $concepts[] = [...$concepto, 'amount' => $amount, 'taxBase' => $amount, 'tax' => $tax];
        }

        return [
            'input' => $input,
            'concepts' => $concepts,
            'subtotal' => $subtotal,
            'totalTransferredTaxes' => $totalTransferredTaxes,
            'subtotalRounded' => $this->roundMoney($subtotal),
            'totalTransferredTaxesRounded' => $this->roundMoney($totalTransferredTaxes),
            'total' => $this->roundMoney(bcadd($subtotal, $totalTransferredTaxes, 6)),
        ];
    }

    private function roundMoney(string $amount): string
    {
        return bcadd($amount, '0.005', 2);
    }
}
