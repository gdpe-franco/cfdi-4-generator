<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi;

use App\Services\Cfdi\CfdiAdvisoryValidator;
use App\Services\Cfdi\CfdiCalculationDispatcher;
use App\Services\Cfdi\CfdiInputValidator;
use App\Services\Cfdi\CfdiXmlGenerator;
use App\Services\Cfdi\IngresoCalculator;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class CfdiAdvisoryValidatorTest extends TestCase
{
    public function test_reports_expected_warnings(): void
    {
        $result = (new CfdiAdvisoryValidator)->validate($this->xml());
        $warning = $this->finding($result['findings'], 'SELLO01');

        $this->assertSame('WARN', $warning['status']);
        $this->assertTrue($warning['expected']);
    }

    public function test_reports_schema_failure(): void
    {
        $xml = $this->xml();
        $xml->documentElement->removeAttribute('Sello');

        $result = (new CfdiAdvisoryValidator)->validate($xml);
        $failure = $this->finding($result['findings'], 'XSD01');

        $this->assertSame('ERROR', $failure['status']);
        $this->assertFalse($failure['expected']);
    }

    private function xml(): DOMDocument
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
        $calculation = (new CfdiCalculationDispatcher(new CfdiInputValidator, new IngresoCalculator))->calculate($input);

        return (new CfdiXmlGenerator)->generate($calculation);
    }

    private function finding(array $findings, string $code): array
    {
        foreach ($findings as $finding) {
            if ($finding['code'] === $code) {
                return $finding;
            }
        }

        $this->fail("Finding {$code} was not returned.");
    }
}
