<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\SatCatalog;
use PhpCfdi\SatCatalogos\Exceptions\SatCatalogosNotFoundException;
use PHPUnit\Framework\TestCase;

class SatCatalogTest extends TestCase
{
    public function test_reads_receiver_regimens(): void
    {
        $entry = (new SatCatalog)->usoCfdi('G01');

        $this->assertContains('603', $entry->regimenesFiscalesReceptoresList());
    }

    public function test_rejects_unknown_usage(): void
    {
        $this->expectException(SatCatalogosNotFoundException::class);

        (new SatCatalog)->usoCfdi('INVALID');
    }
}
