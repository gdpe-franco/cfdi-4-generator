<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\CalculationDispatcher;
use App\Services\Cfdi\V40\IngresoCalculator;
use App\Services\Cfdi\V40\InputValidator;
use App\Services\Cfdi\V40\Schema;
use App\Services\Cfdi\V40\XmlGenerator;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class XmlGeneratorTest extends TestCase
{
    public function test_generates_xml(): void
    {
        $xml = (new XmlGenerator)->generate($this->calculation(), new DateTimeImmutable('2025-02-06 12:30:00', new DateTimeZone('America/Mexico_City')));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('cfdi', Schema::CFDI_NAMESPACE);

        $this->assertSame('9310.69', $xml->documentElement->getAttribute('Total'));
        $this->assertSame('2025-02-06T12:30:00', $xml->documentElement->getAttribute('Fecha'));
        $this->assertSame(Schema::cfdi40SchemaLocation(), $xml->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation'));
        $this->assertEquals(3.0, $xpath->evaluate('count(/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto)'));
        $this->assertSame('1284.23', $xpath->evaluate('string(/cfdi:Comprobante/cfdi:Impuestos/@TotalImpuestosTrasladados)'));
        $this->assertSame('1284.233600', $xpath->evaluate('string(/cfdi:Comprobante/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado/@Importe)'));
    }

    public function test_rejects_unsupported_type(): void
    {
        $calculation = $this->calculation();
        $calculation['input']['comprobante']['tipoDeComprobante'] = 'E';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported CFDI type.');
        (new XmlGenerator)->generate($calculation);
    }

    private function calculation(): array
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);

        return (new CalculationDispatcher(new InputValidator, new IngresoCalculator))->calculate($input);
    }
}
