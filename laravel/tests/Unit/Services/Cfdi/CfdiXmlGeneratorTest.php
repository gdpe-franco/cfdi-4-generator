<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi;

use App\Services\Cfdi\CfdiCalculationDispatcher;
use App\Services\Cfdi\CfdiInputValidator;
use App\Services\Cfdi\CfdiXmlGenerator;
use App\Services\Cfdi\IngresoCalculator;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CfdiXmlGeneratorTest extends TestCase
{
    public function test_generates_xml(): void
    {
        $xml = (new CfdiXmlGenerator)->generate($this->calculation(), new DateTimeImmutable('2025-02-06 12:30:00', new DateTimeZone('America/Mexico_City')));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');

        $this->assertSame('9310.69', $xml->documentElement->getAttribute('Total'));
        $this->assertSame('2025-02-06T12:30:00', $xml->documentElement->getAttribute('Fecha'));
        $this->assertSame('http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/cfd/4/cfdv40.xsd', $xml->documentElement->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation'));
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
        (new CfdiXmlGenerator)->generate($calculation);
    }

    private function calculation(): array
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);

        return (new CfdiCalculationDispatcher(new CfdiInputValidator, new IngresoCalculator))->calculate($input);
    }
}
