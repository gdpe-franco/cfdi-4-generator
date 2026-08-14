# CFDI 4.0 Technical Test

## Purpose

Build a Laravel CLI application in `laravel/` that reads JSON, generates a CFDI 4.0 `Ingreso` XML, writes the deliverable, and validates it locally.

## Boundaries

- Docker Compose provides PHP, Composer, `bcmath`, DOM, and libxml.
- XML is built programmatically with `DOMDocument`; monetary values use `bcmath`, never floats.
- The primary command is `php artisan cfdi:40:generate <input>` and always writes `storage/app/cfdi/cfdi.xml`.
- Official SAT XSD dependencies are committed locally. The PhpCfdi SQLite catalog is a pinned, verified setup resource—not an application database.
- PAC, timbrado, CSD files, real signatures, live RFC checks, APIs, UI, queues, and unsupported document scenarios are out of scope.

## Decisions

- `Ingreso` is the default and only implemented `TipoDeComprobante`; validation and calculation dispatch by type for future extension.
- `Fecha` is generated in `America/Mexico_City`; it is not input data.
- The input supports the supplied concepts with one IVA `Traslado`; no retentions, discounts, or complements.
- Validation layers are: input/catalog/filling-guide gates, local XSD validation, then advisory CfdiUtils checks. Out-of-scope certificate, signature, and timbre findings are logged as skipped.
- The generated example XML is intentionally committed. Other Laravel runtime storage remains ignored.
- Future CSD, PAC, and application features are documented in the README as candidates, not implemented capabilities.

## Phases

1. Bootstrap, trimmed Laravel skeleton, Docker, and local SAT XSD assets — complete.
2. JSON contract, decimal calculations, and `Ingreso` type dispatch — complete.
3. DOM-based CFDI 4.0 XML generation with demonstration certificate fields — complete.
4. Local XSD and supplemental CfdiUtils validation — complete.
5. Static-output Artisan command and generated XML artifact — complete.
6. PHPUnit coverage, formatting, and reviewer documentation — complete.
7. Local SAT catalog and supported `Ingreso` cross-field validation — complete.
8. Type-specific validation and calculation dispatch refinement — complete.
9. Pinned catalog setup delivery and tracked XML artifact handling — complete.
