<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

use RuntimeException;
use Symfony\Component\Process\Process;

class CatalogInstaller
{
    public function __construct(
        private readonly ?string $database = null,
        private readonly ?string $source = null,
        private readonly ?string $sha256 = null,
    ) {}

    public function install(bool $update = false): bool
    {
        $database = $this->database ?? Schema::satCatalogDatabase();
        $sha256 = $this->sha256 ?? Schema::satCatalogSha256();

        if (! $update && is_file($database) && hash_file('sha256', $database) === $sha256) {
            return false;
        }

        $directory = dirname($database);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create catalog directory: {$directory}");
        }

        $temporary = tempnam($directory, 'catalog-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to create a temporary catalog file.');
        }
        unlink($temporary);
        $archive = "{$temporary}.bz2";

        try {
            $this->run(['curl', '--fail', '--location', '--silent', '--show-error', '--output', $archive, $this->source ?? Schema::satCatalogSource()]);
            $this->run(['bzip2', '--decompress', $archive]);

            if (hash_file('sha256', $temporary) !== $sha256) {
                throw new RuntimeException('Downloaded SAT catalog checksum does not match the configured SHA-256.');
            }
            if (! rename($temporary, $database)) {
                throw new RuntimeException("Unable to install SAT catalog: {$database}");
            }
        } finally {
            if (is_file($archive)) {
                unlink($archive);
            }
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }

        return true;
    }

    private function run(array $command): void
    {
        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Unable to download or decompress the SAT catalog.');
        }
    }
}
