<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cfdi\V40\CalculationDispatcher;
use App\Services\Cfdi\V40\XmlGenerator;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class GenerateCfdi40 extends Command
{
    protected $signature = 'cfdi:40:generate
        {input : Path to the CFDI JSON input file}';

    protected $description = 'Generate a CFDI 4.0 XML file from JSON input';

    public function handle(CalculationDispatcher $calculator, XmlGenerator $generator): int
    {
        try {
            $calculation = $calculator->calculate($this->inputData());
            $output = base_path('storage/app/cfdi/cfdi.xml');
            $xml = $generator->generate($calculation)->saveXML();

            if ($xml === false) {
                throw new RuntimeException('Unable to serialize the generated XML.');
            }

            $directory = dirname($output);
            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException("Unable to create output directory: {$directory}");
            }
            if (file_put_contents($output, $xml) === false) {
                throw new RuntimeException("Unable to write XML file: {$output}");
            }
        } catch (JsonException $exception) {
            $this->error("Invalid JSON: {$exception->getMessage()}");

            return self::FAILURE;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Generated XML: {$output}");

        return self::SUCCESS;
    }

    private function inputData(): array
    {
        $path = $this->resolvePath($this->argument('input'));
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("Input file is missing or unreadable: {$path}");
        }

        $input = json_decode(file_get_contents($path) ?: '', true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($input)) {
            throw new InvalidArgumentException('Input JSON must contain an object.');
        }

        return $input;
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }
}
