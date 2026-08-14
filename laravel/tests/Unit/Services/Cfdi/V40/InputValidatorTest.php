<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cfdi\V40;

use App\Services\Cfdi\V40\InputValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InputValidatorTest extends TestCase
{
    public function test_defaults_type(): void
    {
        $input = $this->fixture();
        unset($input['comprobante']['tipoDeComprobante']);

        $normalized = (new InputValidator)->normalize($input);

        $this->assertSame('I', $normalized['comprobante']['tipoDeComprobante']);
    }

    #[DataProvider('invalidInputProvider')]
    public function test_rejects_invalid_input(string $path, mixed $value, string $message): void
    {
        $input = $this->fixture();
        $this->setValue($input, $path, $value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        (new InputValidator)->normalize($input);
    }

    public static function invalidInputProvider(): iterable
    {
        yield 'unknown field' => ['receptor.misspelledField', 'value', 'receptor.misspelledField is not allowed.'];
        yield 'unsupported IVA rate' => ['conceptos.0.iva', '0.100000', 'conceptos.0.iva must be one of: 0.000000, 0.080000, 0.160000.'];
        yield 'zero quantity' => ['conceptos.0.cantidad', '0', 'conceptos.0.cantidad must be a positive decimal with up to six decimal places.'];
    }

    private function fixture(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/cfdi-input.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function setValue(array &$input, string $path, mixed $value): void
    {
        $target = &$input;
        foreach (explode('.', $path) as $segment) {
            $target = &$target[$segment];
        }
        $target = $value;
    }
}
