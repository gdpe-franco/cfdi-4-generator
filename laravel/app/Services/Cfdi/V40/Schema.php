<?php

declare(strict_types=1);

namespace App\Services\Cfdi\V40;

class Schema
{
    public const CFDI_NAMESPACE = 'http://www.sat.gob.mx/cfd/4';

    private const XSD_ROOT = '/resources/xsd';

    private const CFDI_40_SCHEMA = '/www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd';

    private const CFDI_40_SCHEMA_URL = 'http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd';

    private const SAT_CATALOG_DATABASE = '/resources/sat/catalogs.db';

    public static function xsdRoot(): string
    {
        return dirname(__DIR__, 4).self::XSD_ROOT;
    }

    public static function cfdi40Schema(): string
    {
        return self::xsdRoot().self::CFDI_40_SCHEMA;
    }

    public static function cfdi40SchemaLocation(): string
    {
        return self::CFDI_NAMESPACE.' '.self::CFDI_40_SCHEMA_URL;
    }

    public static function satCatalogDatabase(): string
    {
        return dirname(__DIR__, 4).self::SAT_CATALOG_DATABASE;
    }
}
