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
- validation summaries; XSD failures include line, column, and message; and
- the count of out-of-scope certificate, signature, and timbre checks recorded in Laravel's standard log.

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

## Validation layers

The command applies three distinct layers:

1. Input, catalog, and filling-guide checks run before XML creation and fail the command when the supported `Ingreso` input is invalid.
2. `DOMDocument::schemaValidate()` validates the generated XML with the bundled official SAT XSD dependencies and fails the command on an XSD error.
3. CfdiUtils performs supplemental structural checks with the same local resources. Applicable findings are advisory; certificate, signature, and `TimbreFiscalDigital` checks are skipped because they are explicitly out of scope and their codes are logged.

The project never downloads validation resources at runtime. The official XSD dependencies are under `laravel/resources/xsd/`, and the read-only SAT catalog resource is under `laravel/resources/sat/`.

For implementation and learning details, including the supported `Ingreso` filling-guide rules and catalog update process, see [CFDI_STUDY.md](docs/CFDI_STUDY.md).

## Important disclaimer

Structural XSD validity is not legal or fiscal validity. The generated XML is not timbrado, has no `TimbreFiscalDigital`, and uses demonstration certificate/signature values. It must not be used as an invoice or presented as accepted by SAT or any PAC.

## Further reading

See [PROJECT.md](PROJECT.md) for scope decisions and phases.
