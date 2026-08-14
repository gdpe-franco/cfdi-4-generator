<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\BusinessValidationDispatcher;
use App\Services\Cfdi\V40\CalculationDispatcher;
use App\Services\Cfdi\V40\IngresoBusinessValidator;
use App\Services\Cfdi\V40\IngresoCalculator;
use App\Services\Cfdi\V40\InputValidator;
use App\Services\Cfdi\V40\SatCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalculationDispatcherTest extends TestCase
{
    public function test_calculates_totals(): void
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
        $result = $this->dispatcher()->calculate($input);

        $this->assertSame('8026.460000', $result['subtotal']);
        $this->assertSame('1284.233600', $result['totalTransferredTaxes']);
        $this->assertSame('8026.46', $result['subtotalRounded']);
        $this->assertSame('1284.23', $result['totalTransferredTaxesRounded']);
        $this->assertSame('9310.69', $result['total']);
        $this->assertSame('367.401600', $result['concepts'][0]['tax']);
    }

    public function test_rejects_unsupported_type(): void
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
        $input['comprobante']['tipoDeComprobante'] = 'E';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('comprobante.tipoDeComprobante "E" is not supported.');
        $this->dispatcher()->calculate($input);
    }

    private function dispatcher(): CalculationDispatcher
    {
        return new CalculationDispatcher(
            new InputValidator,
            new BusinessValidationDispatcher(new IngresoBusinessValidator(new SatCatalog)),
            new IngresoCalculator,
        );
    }
}
