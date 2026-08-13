<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

use InvalidArgumentException;

class CalculationDispatcher
{
    public function __construct(
        private InputValidator $inputValidator,
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
