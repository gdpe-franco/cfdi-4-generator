<?php

declare(strict_types=1);

namespace App\Services\Cfdi;

use InvalidArgumentException;

class CfdiInputValidator
{
    private const IVA_RATES = ['0.000000', '0.080000', '0.160000'];

    private const TOP_LEVEL_FIELDS = ['comprobante', 'emisor', 'receptor', 'conceptos'];

    private const COMPROBANTE_FIELDS = ['version', 'tipoDeComprobante', 'exportacion', 'formaPago', 'metodoPago', 'tipoCambio', 'moneda', 'lugarExpedicion', 'serie', 'folio'];

    private const EMISOR_FIELDS = ['rfc', 'nombre', 'regimenFiscal'];

    private const RECEPTOR_FIELDS = ['rfc', 'nombre', 'regimenFiscalReceptor', 'domicilioFiscalReceptor', 'usoCFDI'];

    private const CONCEPTO_FIELDS = ['cantidad', 'claveUnidad', 'unidad', 'valorUnitario', 'claveProdServ', 'descripcion', 'objetoImp', 'iva'];

    public function normalize(array $input): array
    {
        $this->assertAllowed($input, self::TOP_LEVEL_FIELDS, 'input');

        foreach (self::TOP_LEVEL_FIELDS as $section) {
            if (! isset($input[$section]) || ! is_array($input[$section])) {
                throw new InvalidArgumentException("{$section} must be an object.");
            }
        }

        $comprobante = $input['comprobante'];
        $this->assertAllowed($comprobante, self::COMPROBANTE_FIELDS, 'comprobante');
        $comprobante['tipoDeComprobante'] ??= 'I';
        $this->requireStrings($comprobante, self::COMPROBANTE_FIELDS, 'comprobante');

        if ($comprobante['tipoDeComprobante'] !== 'I') {
            throw new InvalidArgumentException("comprobante.tipoDeComprobante \"{$comprobante['tipoDeComprobante']}\" is not supported.");
        }

        $this->assertStringObject($input['emisor'], self::EMISOR_FIELDS, 'emisor');
        $this->assertStringObject($input['receptor'], self::RECEPTOR_FIELDS, 'receptor');

        if (! array_is_list($input['conceptos']) || $input['conceptos'] === []) {
            throw new InvalidArgumentException('conceptos must be a non-empty list.');
        }

        foreach ($input['conceptos'] as $index => $concepto) {
            $path = "conceptos.{$index}";
            if (! is_array($concepto)) {
                throw new InvalidArgumentException("{$path} must be an object.");
            }
            $this->assertStringObject($concepto, self::CONCEPTO_FIELDS, $path);
            $this->requirePositiveDecimal($concepto['cantidad'], "{$path}.cantidad");
            $this->requirePositiveDecimal($concepto['valorUnitario'], "{$path}.valorUnitario");

            if (! in_array($concepto['iva'], self::IVA_RATES, true)) {
                throw new InvalidArgumentException("{$path}.iva must be one of: ".implode(', ', self::IVA_RATES).'.');
            }
        }

        return [...$input, 'comprobante' => $comprobante];
    }

    private function assertAllowed(array $input, array $allowed, string $path): void
    {
        foreach (array_keys($input) as $field) {
            if (! in_array($field, $allowed, true)) {
                throw new InvalidArgumentException("{$path}.{$field} is not allowed.");
            }
        }
    }

    private function assertStringObject(array $input, array $fields, string $path): void
    {
        $this->assertAllowed($input, $fields, $path);
        $this->requireStrings($input, $fields, $path);
    }

    private function requireStrings(array $input, array $fields, string $path): void
    {
        foreach ($fields as $field) {
            if (! isset($input[$field]) || ! is_string($input[$field]) || $input[$field] === '') {
                throw new InvalidArgumentException("{$path}.{$field} must be a non-empty string.");
            }
        }
    }

    private function requirePositiveDecimal(string $value, string $path): void
    {
        if (! preg_match('/^\d+(?:\.\d{1,6})?$/', $value) || bccomp($value, '0', 6) !== 1) {
            throw new InvalidArgumentException("{$path} must be a positive decimal with up to six decimal places.");
        }
    }
}
