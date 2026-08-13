<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class XmlGenerator
{
    private const XSI_NAMESPACE = 'http://www.w3.org/2001/XMLSchema-instance';

    private const IVA = '002';

    private const TAX_FACTOR = 'Tasa';

    public function generate(array $calculation, ?DateTimeInterface $issuedAt = null): DOMDocument
    {
        if (($calculation['input']['comprobante']['tipoDeComprobante'] ?? null) !== 'I') {
            throw new InvalidArgumentException('Unsupported CFDI type.');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $input = $calculation['input'];
        $comprobante = $input['comprobante'];
        $root = $this->element($document, 'Comprobante', [
            'Version' => $comprobante['version'],
            'Serie' => $comprobante['serie'],
            'Folio' => $comprobante['folio'],
            'Fecha' => ($issuedAt ?? new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('Y-m-d\\TH:i:s'),
            'Sello' => 'REVNT05TVFJBVElPTi1PTkxZ',
            'FormaPago' => $comprobante['formaPago'],
            'NoCertificado' => '00001000000500000000',
            'Certificado' => 'REVNT05TVFJBVElPTi1DRVJUSUZJQ0FURQ==',
            'SubTotal' => $calculation['subtotalRounded'],
            'Moneda' => $comprobante['moneda'],
            'Total' => $calculation['total'],
            'TipoDeComprobante' => $comprobante['tipoDeComprobante'],
            'Exportacion' => $comprobante['exportacion'],
            'MetodoPago' => $comprobante['metodoPago'],
            'LugarExpedicion' => $comprobante['lugarExpedicion'],
        ]);
        $root->setAttributeNS(self::XSI_NAMESPACE, 'xsi:schemaLocation', Schema::cfdi40SchemaLocation());
        $document->appendChild($root);

        $root->appendChild($this->element($document, 'Emisor', [
            'Rfc' => $input['emisor']['rfc'],
            'Nombre' => $input['emisor']['nombre'],
            'RegimenFiscal' => $input['emisor']['regimenFiscal'],
        ]));
        $root->appendChild($this->element($document, 'Receptor', [
            'Rfc' => $input['receptor']['rfc'],
            'Nombre' => $input['receptor']['nombre'],
            'DomicilioFiscalReceptor' => $input['receptor']['domicilioFiscalReceptor'],
            'RegimenFiscalReceptor' => $input['receptor']['regimenFiscalReceptor'],
            'UsoCFDI' => $input['receptor']['usoCFDI'],
        ]));

        $conceptos = $this->element($document, 'Conceptos');
        foreach ($calculation['concepts'] as $concept) {
            $conceptos->appendChild($this->concept($document, $concept));
        }
        $root->appendChild($conceptos);
        $root->appendChild($this->taxes($document, $calculation));

        return $document;
    }

    private function concept(DOMDocument $document, array $concept): DOMElement
    {
        $element = $this->element($document, 'Concepto', [
            'ClaveProdServ' => $concept['claveProdServ'],
            'Cantidad' => $concept['cantidad'],
            'ClaveUnidad' => $concept['claveUnidad'],
            'Unidad' => $concept['unidad'],
            'Descripcion' => $concept['descripcion'],
            'ValorUnitario' => $concept['valorUnitario'],
            'Importe' => $concept['amount'],
            'ObjetoImp' => $concept['objetoImp'],
        ]);
        $impuestos = $this->element($document, 'Impuestos');
        $traslados = $this->element($document, 'Traslados');
        $traslados->appendChild($this->element($document, 'Traslado', [
            'Base' => $concept['taxBase'],
            'Impuesto' => self::IVA,
            'TipoFactor' => self::TAX_FACTOR,
            'TasaOCuota' => $concept['iva'],
            'Importe' => $concept['tax'],
        ]));
        $impuestos->appendChild($traslados);
        $element->appendChild($impuestos);

        return $element;
    }

    private function taxes(DOMDocument $document, array $calculation): DOMElement
    {
        $impuestos = $this->element($document, 'Impuestos', [
            'TotalImpuestosTrasladados' => $calculation['totalTransferredTaxesRounded'],
        ]);
        $traslados = $this->element($document, 'Traslados');
        foreach ($this->taxGroups($calculation['concepts']) as $rate => $group) {
            $traslados->appendChild($this->element($document, 'Traslado', [
                'Base' => $group['base'],
                'Impuesto' => self::IVA,
                'TipoFactor' => self::TAX_FACTOR,
                'TasaOCuota' => $rate,
                'Importe' => $group['tax'],
            ]));
        }
        $impuestos->appendChild($traslados);

        return $impuestos;
    }

    private function taxGroups(array $concepts): array
    {
        $groups = [];

        foreach ($concepts as $concept) {
            $rate = $concept['iva'];
            $groups[$rate] ??= ['base' => '0.000000', 'tax' => '0.000000'];
            $groups[$rate]['base'] = bcadd($groups[$rate]['base'], $concept['taxBase'], 6);
            $groups[$rate]['tax'] = bcadd($groups[$rate]['tax'], $concept['tax'], 6);
        }

        return $groups;
    }

    private function element(DOMDocument $document, string $name, array $attributes = []): DOMElement
    {
        $element = $document->createElementNS(Schema::CFDI_NAMESPACE, "cfdi:{$name}");

        foreach ($attributes as $name => $value) {
            $element->setAttribute($name, $value);
        }

        return $element;
    }
}
