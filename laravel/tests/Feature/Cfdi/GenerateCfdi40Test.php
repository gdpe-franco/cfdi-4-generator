<?php

declare(strict_types=1);

namespace Tests\Feature\Cfdi;

use Illuminate\Console\Command;
use Tests\TestCase;

class GenerateCfdi40Test extends TestCase
{
    public function test_generates_xml(): void
    {
        $input = $this->temporaryFile(file_get_contents(__DIR__.'/../../Fixtures/cfdi-input.json'));
        $output = storage_path('app/cfdi/cfdi.xml');
        $previous = is_file($output) ? file_get_contents($output) : null;

        try {
            $this->artisan('cfdi:40:generate', ['input' => $input])
                ->expectsOutput("Generated XML: {$output}")
                ->expectsOutput('XSD validation: valid.')
                ->expectsOutputToContain('Advisory findings:')
                ->assertExitCode(Command::SUCCESS);

            $this->assertFileExists($output);
            $xml = new \DOMDocument;
            $xml->load($output);
            $this->assertSame('9310.69', $xml->documentElement->getAttribute('Total'));
        } finally {
            @unlink($input);
            $previous === null ? @unlink($output) : file_put_contents($output, $previous);
        }
    }

    public function test_rejects_malformed_json(): void
    {
        $input = $this->temporaryFile('{');

        try {
            $this->artisan('cfdi:40:generate', ['input' => $input])
                ->expectsOutputToContain('Invalid JSON:')
                ->assertExitCode(Command::FAILURE);
        } finally {
            @unlink($input);
        }
    }

    private function temporaryFile(?string $contents = null): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cfdi-');
        if ($path === false) {
            $this->fail('Unable to create a temporary file.');
        }

        if ($contents !== null && file_put_contents($path, $contents) === false) {
            $this->fail('Unable to write a temporary file.');
        }

        return $path;
    }
}
