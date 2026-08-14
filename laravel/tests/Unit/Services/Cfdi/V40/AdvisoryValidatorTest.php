<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\AdvisoryValidator;
use App\Services\Cfdi\V40\BusinessValidationDispatcher;
use App\Services\Cfdi\V40\CalculationDispatcher;
use App\Services\Cfdi\V40\IngresoBusinessValidator;
use App\Services\Cfdi\V40\IngresoCalculator;
use App\Services\Cfdi\V40\InputValidator;
use App\Services\Cfdi\V40\SatCatalog;
use App\Services\Cfdi\V40\XmlGenerator;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class AdvisoryValidatorTest extends TestCase
{
    public function test_skips_out_of_scope_checks(): void
    {
        $result = (new AdvisoryValidator)->validate($this->xml());
        $finding = $this->finding($result['skipped'], 'SELLO01');

        $this->assertSame('ERROR', $finding['status']);
    }

    public function test_reports_schema_failure(): void
    {
        $xml = $this->xml();
        $xml->documentElement->removeAttribute('Sello');

        $result = (new AdvisoryValidator)->validate($xml);
        $failure = $this->finding($result['findings'], 'XSD01');

        $this->assertSame('ERROR', $failure['status']);
    }

    private function xml(): DOMDocument
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
        $calculation = (new CalculationDispatcher(new InputValidator, new BusinessValidationDispatcher(new IngresoBusinessValidator(new SatCatalog)), new IngresoCalculator))->calculate($input);

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
