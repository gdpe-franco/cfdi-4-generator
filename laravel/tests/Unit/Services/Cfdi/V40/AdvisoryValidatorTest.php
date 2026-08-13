<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\AdvisoryValidator;
use App\Services\Cfdi\V40\CalculationDispatcher;
use App\Services\Cfdi\V40\IngresoCalculator;
use App\Services\Cfdi\V40\InputValidator;
use App\Services\Cfdi\V40\XmlGenerator;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class AdvisoryValidatorTest extends TestCase
{
    public function test_reports_expected_warnings(): void
    {
        $result = (new AdvisoryValidator)->validate($this->xml());
        $warning = $this->finding($result['findings'], 'SELLO01');

        $this->assertSame('WARN', $warning['status']);
        $this->assertTrue($warning['expected']);
    }

    public function test_reports_schema_failure(): void
    {
        $xml = $this->xml();
        $xml->documentElement->removeAttribute('Sello');

        $result = (new AdvisoryValidator)->validate($xml);
        $failure = $this->finding($result['findings'], 'XSD01');

        $this->assertSame('ERROR', $failure['status']);
        $this->assertFalse($failure['expected']);
    }

    private function xml(): DOMDocument
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
        $calculation = (new CalculationDispatcher(new InputValidator, new IngresoCalculator))->calculate($input);

        return (new XmlGenerator)->generate($calculation);
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
