<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi;

use App\Services\Cfdi\CfdiCalculationDispatcher;
use App\Services\Cfdi\CfdiInputValidator;
use App\Services\Cfdi\CfdiXmlGenerator;
use App\Services\Cfdi\CfdiXsdValidator;
use App\Services\Cfdi\IngresoCalculator;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class CfdiXsdValidatorTest extends TestCase
{
    public function test_validates_xml(): void
    {
        $result = (new CfdiXsdValidator)->validate($this->xml());

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
    }

    public function test_reports_invalid_xml(): void
    {
        $xml = $this->xml();
        $xml->documentElement->removeAttribute('Sello');

        $result = (new CfdiXsdValidator)->validate($xml);

        $this->assertFalse($result['valid']);
        $this->assertIsInt($result['errors'][0]['line']);
        $this->assertIsInt($result['errors'][0]['column']);
        $this->assertStringContainsString('Sello', $result['errors'][0]['message']);
    }

    private function xml(): DOMDocument
    {
        $input = json_decode(file_get_contents(__DIR__.'/../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
        $calculation = (new CfdiCalculationDispatcher(new CfdiInputValidator, new IngresoCalculator))->calculate($input);

        return (new CfdiXmlGenerator)->generate($calculation);
    }
}
