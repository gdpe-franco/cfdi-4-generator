<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cfdi\V40\AdvisoryValidator;
use App\Services\Cfdi\V40\CalculationDispatcher;
use App\Services\Cfdi\V40\XmlGenerator;
use App\Services\Cfdi\V40\XsdValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class GenerateCfdi40 extends Command
{
    protected $signature = 'cfdi:40:generate
        {input : Path to the CFDI JSON input file}';

    protected $description = 'Generate a CFDI 4.0 XML file from JSON input';

    public function handle(
        CalculationDispatcher $calculator,
        XmlGenerator $generator,
        XsdValidator $xsdValidator,
        AdvisoryValidator $advisoryValidator,
    ): int {
        try {
            $calculation = $calculator->calculate($this->inputData());
            $output = base_path('storage/app/cfdi/cfdi.xml');
            $document = $generator->generate($calculation);
            $xml = $document->saveXML();

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

            $xsd = $xsdValidator->validate($document);
            $advisory = $xsd['valid'] ? $advisoryValidator->validate($document) : ['findings' => [], 'skipped' => []];
        } catch (JsonException $exception) {
            $this->error("Invalid JSON: {$exception->getMessage()}");

            return self::FAILURE;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Generated XML: {$output}");

        if (! $xsd['valid']) {
            $this->error('XSD validation: failed.');
            foreach ($xsd['errors'] as $error) {
                $this->error("Line {$error['line']}, column {$error['column']}: {$error['message']}");
            }

            return self::FAILURE;
        }

        $this->info('XSD validation: valid.');
        $this->reportAdvisoryFindings($advisory['findings'], $advisory['skipped']);

        return self::SUCCESS;
    }

    private function inputData(): array
    {
        $inputPath = $this->argument('input');
        $path = str_starts_with($inputPath, DIRECTORY_SEPARATOR) ? $inputPath : base_path($inputPath);
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("Input file is missing or unreadable: {$path}");
        }

        $input = json_decode(file_get_contents($path) ?: '', true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($input)) {
            throw new InvalidArgumentException('Input JSON must contain an object.');
        }

        return $input;
    }

    private function reportAdvisoryFindings(array $findings, array $skipped): void
    {
        if ($findings === []) {
            $this->info('CfdiUtils structural checks: passed.');
        } else {
            $this->warn('CfdiUtils advisory findings:');
            foreach ($findings as $finding) {
                $this->line("- {$finding['status']} {$finding['code']}: {$finding['title']}");
            }
        }

        if ($skipped === []) {
            return;
        }

        Log::info('CfdiUtils checks skipped for demonstration XML.', [
            'count' => count($skipped),
            'codes' => array_column($skipped, 'code'),
        ]);
        $this->line('CfdiUtils checks skipped: '.count($skipped).' certificate, signature, and timbre checks. Details are in the Laravel log.');
    }
}
