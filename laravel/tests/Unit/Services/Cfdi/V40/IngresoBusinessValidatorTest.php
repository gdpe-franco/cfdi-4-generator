<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\IngresoBusinessValidator;
use App\Services\Cfdi\V40\SatCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IngresoBusinessValidatorTest extends TestCase
{
    public function test_accepts_fixture(): void
    {
        $this->expectNotToPerformAssertions();

        (new IngresoBusinessValidator(new SatCatalog))->validate($this->fixture());
    }

    #[DataProvider('invalidInputProvider')]
    public function test_rejects_invalid_input(array $values, string $message): void
    {
        $input = $this->fixture();
        foreach ($values as $path => $value) {
            $this->setValue($input, $path, $value);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        (new IngresoBusinessValidator(new SatCatalog))->validate($input);
    }

    public static function invalidInputProvider(): iterable
    {
        yield 'payment method' => [['comprobante.formaPago' => '99', 'comprobante.metodoPago' => 'PUE'], 'comprobante.formaPago 99 requires comprobante.metodoPago PPD.'];
        yield 'foreign trade' => [['comprobante.exportacion' => '02'], 'comprobante.exportacion 02 requires the Comercio Exterior complement, which is unsupported.'];
        yield 'generic foreign RFC' => [['receptor.rfc' => 'XEXX010101000'], 'receptor.rfc XEXX010101000 requires receptor.regimenFiscalReceptor 616 and receptor.usoCFDI S01.'];
        yield 'receiver regimen' => [['receptor.regimenFiscalReceptor' => '605'], 'receptor.usoCFDI "G01" is incompatible with receptor.regimenFiscalReceptor "605".'];
    }

    private function fixture(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function setValue(array &$input, string $path, string $value): void
    {
        $target = &$input;
        foreach (explode('.', $path) as $segment) {
            $target = &$target[$segment];
        }
        $target = $value;
    }
}
