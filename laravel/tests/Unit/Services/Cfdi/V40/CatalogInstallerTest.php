<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\CatalogInstaller;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class CatalogInstallerTest extends TestCase
{
    public function test_installs_resource(): void
    {
        [$database, $source, $contents] = $this->resource();

        try {
            $installer = new CatalogInstaller($database, $source, hash('sha256', $contents));

            $this->assertTrue($installer->install());
            $this->assertSame($contents, file_get_contents($database));
            $this->assertFalse($installer->install());
            $this->assertTrue($installer->install(true));
        } finally {
            $this->delete($database);
            $this->delete(substr($source, 7));
        }
    }

    public function test_rejects_invalid_checksum(): void
    {
        [$database, $source] = $this->resource();

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('checksum');
            (new CatalogInstaller($database, $source, str_repeat('0', 64)))->install();
        } finally {
            $this->delete($database);
            $this->delete(substr($source, 7));
        }
    }

    private function resource(): array
    {
        $plain = tempnam(sys_get_temp_dir(), 'catalog-');
        if ($plain === false || file_put_contents($plain, 'catalog resource') === false) {
            $this->fail('Unable to create a catalog fixture.');
        }
        (new Process(['bzip2', '--force', $plain]))->mustRun();

        return ["{$plain}.db", "file://{$plain}.bz2", 'catalog resource'];
    }

    private function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
