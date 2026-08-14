<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

use InvalidArgumentException;

class BusinessValidationDispatcher
{
    public function __construct(private IngresoBusinessValidator $ingresoBusinessValidator) {}

    public function validate(array $input): void
    {
        $type = $input['comprobante']['tipoDeComprobante'];

        switch ($type) {
            case 'I':
                $this->ingresoBusinessValidator->validate($input);

                return;
            default:
                throw new InvalidArgumentException("comprobante.tipoDeComprobante \"{$type}\" is not supported.");
        }
    }
}
