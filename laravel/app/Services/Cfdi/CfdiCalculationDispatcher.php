<?php

declare(strict_types=1);

namespace App\Services\Cfdi;

use InvalidArgumentException;

class CfdiCalculationDispatcher
{
    public function __construct(
        private CfdiInputValidator $inputValidator,
        private IngresoCalculator $ingresoCalculator,
    ) {}

    public function calculate(array $input): array
    {
        $normalized = $this->inputValidator->normalize($input);

        return match ($normalized['comprobante']['tipoDeComprobante']) {
            'I' => $this->ingresoCalculator->calculate($normalized),
            default => throw new InvalidArgumentException('Unsupported CFDI type.'),
        };
    }
}
