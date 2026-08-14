# CFDI 4.0 Generator

A focused Laravel technical test that reads structured JSON, creates a CFDI 4.0 `Ingreso` XML, saves it locally, and validates its structure against bundled official SAT schemas.

This is a demonstration project. It does not integrate a PAC, perform timbrado, use `.cer` or `.key` files, produce a real `Sello`, or issue a legally valid invoice. It has no database, authentication, API, UI, queue, Docker runtime customization, or external runtime integration.

## Requirements

- Docker Compose
- Docker permission for the current user

The container supplies PHP 8.4, Composer, `bcmath`, DOM, libxml, and XSL. The Laravel application is located in `laravel/`.

## Installation

From the repository root:

```bash
docker compose build
docker compose run --rm php composer setup
```

## Generate the example XML

Run the command from the repository root:

```bash
docker compose run --rm php php artisan cfdi:40:generate resources/examples/input.json
```

The XML is always written to [laravel/storage/app/cfdi/cfdi.xml](laravel/storage/app/cfdi/cfdi.xml). The committed file is regenerated from the supplied [input.json](laravel/resources/examples/input.json).

The command prints:

- the generated path;
- the local XSD pass/fail result, including line, column, and message on failure; and
- separate advisory findings from CfdiUtils. Findings caused by demonstration `Sello`, `NoCertificado`, or `Certificado` values are marked as expected warnings.

## Tests and formatting

```bash
docker compose run --rm php php artisan test
docker compose run --rm php ./vendor/bin/pint
```

## Architecture

`App\\Services\\Cfdi\\V40` contains the CFDI 4.0 implementation:

| Component | Responsibility |
| --- | --- |
| `InputValidator` | Enforces the first JSON contract and explicit business-input checks. |
| `SatCatalog` / `BusinessValidationDispatcher` / `IngresoBusinessValidator` | Reads the bundled SAT catalog resource, selects the validator for the CFDI type, and enforces supported filling-guide cross-field rules before calculation. |
| `CalculationDispatcher` / `IngresoCalculator` | Selects the calculator for the CFDI type and calculates `Ingreso` amounts using `bcmath`. |
| `XmlGenerator` | Builds every CFDI element and attribute with `DOMDocument`. |
| `XsdValidator` | Runs `DOMDocument::schemaValidate()` and returns normalized libxml errors. |
| `AdvisoryValidator` | Runs CfdiUtils checks with local SAT resources and no network fallback. |
| `GenerateCfdi40` | Orchestrates input, generation, file output, validation, and terminal output. |

`Ingreso` (`TipoDeComprobante = I`) is the only implemented type. The two small dispatchers are the extension points for future type-specific validators and calculators; no unused type handlers or repositories are included.

## Calculations and rounding

All monetary values are decimal strings and are calculated with `bcmath`; PHP floats are never used.

For each `Concepto`:

```text
Importe = Cantidad × ValorUnitario
Base = Importe
IVA = Base × TasaOCuota
```

Intermediate concept amounts, bases, and taxes use six decimal places. Monetary document totals use two decimals, rounded half up for the non-negative amounts allowed by the input contract. The supplied example produces:

| Value | Result |
| --- | --- |
| `SubTotal` | `8026.46` |
| IVA before aggregate rounding | `1284.233600` |
| `TotalImpuestosTrasladados` | `1284.23` |
| `Total` | `9310.69` |

The initial contract supports one IVA `Traslado` per concept at `0.000000`, `0.080000`, or `0.160000`; it does not support retentions, discounts, complements, or other tax scenarios.

## Offline XSD validation

The official SAT `cfdv40.xsd` and its imported dependencies live under `laravel/resources/xsd/www.sat.gob.mx/`. `XsdValidator` passes the local `cfdv40.xsd` path directly to `DOMDocument::schemaValidate()`, so structural validation does not depend on network access. CfdiUtils uses the same local resource tree and rejects missing resources rather than downloading them.

XSD validation checks XML structure and schema constraints. It does not prove that the transaction is correct, the RFCs or catalog selections are valid for the parties, the certificate/signature is authentic, or a PAC/SAT will accept the document.

## Local catalog and business validation

`phpcfdi/sat-catalogos` reads the bundled, read-only SAT catalog database in `laravel/resources/sat/catalogs.db`; its pinned upstream release and checksum are recorded beside the resource. The catalog database is reference data, not an application database, and it is never downloaded at runtime.

`BusinessValidationDispatcher` selects `IngresoBusinessValidator` for the supported `Ingreso` model. It applies locally evaluable filling-guide rules before calculation: `FormaPago=99` requires `MetodoPago=PPD` and vice versa; `Exportacion=02` is rejected because this project does not generate the required Comercio Exterior complement; generic foreign RFC `XEXX010101000` requires `RegimenFiscalReceptor=616` and `UsoCFDI=S01`; and `UsoCFDI` must allow the supplied receiver regime according to the SAT catalog relation.

This layer does not replace PAC/SAT checks. It cannot verify live RFC status, issuer registration, certificate validity, PAC-issued `Confirmacion`, or timbrado acceptance.

## Important disclaimer

Structural XSD validity is not legal or fiscal validity. The generated XML is not timbrado, has no `TimbreFiscalDigital`, and uses demonstration certificate/signature values. It must not be used as an invoice or presented as accepted by SAT or any PAC.

## Further reading

See [PROJECT.md](PROJECT.md) for scope decisions and phases, and [CFDI_STUDY.md](docs/CFDI_STUDY.md) for a learning guide with official SAT resources.
