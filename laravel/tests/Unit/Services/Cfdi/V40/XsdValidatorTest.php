<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\BusinessValidationDispatcher;
use App\Services\Cfdi\V40\CalculationDispatcher;
use App\Services\Cfdi\V40\IngresoBusinessValidator;
use App\Services\Cfdi\V40\IngresoCalculator;
use App\Services\Cfdi\V40\InputValidator;
use App\Services\Cfdi\V40\SatCatalog;
use App\Services\Cfdi\V40\XmlGenerator;
use App\Services\Cfdi\V40\XsdValidator;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class XsdValidatorTest extends TestCase
{
    public function test_validates_xml(): void
    {
        $result = (new XsdValidator)->validate($this->xml());

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    public function test_reports_invalid_xml(): void
    {
        $xml = $this->xml();
        $xml->documentElement->removeAttribute('Sello');

        $result = (new XsdValidator)->validate($xml);

        $this->assertFalse($result['valid']);
        $this->assertIsInt($result['errors'][0]['line']);
        $this->assertIsInt($result['errors'][0]['column']);
        $this->assertStringContainsString('Sello', $result['errors'][0]['message']);
    }

    private function xml(): DOMDocument
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
        $calculation = (new CalculationDispatcher(new InputValidator, new BusinessValidationDispatcher(new IngresoBusinessValidator(new SatCatalog)), new IngresoCalculator))->calculate($input);

        return (new XmlGenerator)->generate($calculation);
    }
}
