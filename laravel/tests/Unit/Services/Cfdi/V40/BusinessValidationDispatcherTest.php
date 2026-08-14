<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\BusinessValidationDispatcher;
use App\Services\Cfdi\V40\IngresoBusinessValidator;
use App\Services\Cfdi\V40\SatCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BusinessValidationDispatcherTest extends TestCase
{
    public function test_validates_ingreso(): void
    {
        $this->expectNotToPerformAssertions();

        $this->dispatcher()->validate($this->fixture());
    }

    public function test_rejects_unsupported_type(): void
    {
        $input = $this->fixture();
        $input['comprobante']['tipoDeComprobante'] = 'E';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('comprobante.tipoDeComprobante "E" is not supported.');
        $this->dispatcher()->validate($input);
    }

    private function dispatcher(): BusinessValidationDispatcher
    {
        return new BusinessValidationDispatcher(new IngresoBusinessValidator(new SatCatalog));
    }

    private function fixture(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
    }
}
