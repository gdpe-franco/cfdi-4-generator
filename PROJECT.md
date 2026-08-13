# CFDI 4.0 Technical Test

## Scope

Build a Laravel command-line project in `laravel/` that reads structured JSON, creates a CFDI 4.0 `Ingreso` XML, saves it, and validates it against locally stored official SAT schemas.

It is a demonstration only. PAC integration, timbrado, CSD files, certificates, real signatures, databases, APIs, UI, queues, and external integrations are out of scope.

## Constraints

- Docker Compose supplies PHP, Composer, `bcmath`, DOM, and libxml.
- Use `bcmath` for monetary calculations; do not use PHP floats.
- Build XML with `DOMDocument`, never a static XML template.
- Validate offline using `DOMDocument::schemaValidate()` and local SAT XSD dependencies.
- The primary interface is `php artisan cfdi:40:generate <input>`.

## Decisions

- `Ingreso` (`I`) is the default and only implemented `TipoDeComprobante`.
- A small dispatcher selects a type-specific handler. Adding another CFDI type later requires a new handler; no unimplemented handlers or database repositories will be created now.
- `Fecha` is created automatically in the `America/Mexico_City` timezone and written to the XML. It is not read from JSON.
- The command always writes `storage/app/cfdi/cfdi.xml`; the generated example XML is committed there.
- The first input contract supports the supplied shape: concepts with one IVA `Traslado`; no retentions, discounts, or complements.
- Validation includes JSON/schema-shape checks, decimal and non-negative monetary values, required fields, calculation consistency, and local SAT XSD validation.
- `eclipxe/cfdiutils` provides an advisory CFDI-rule report using local SAT resources. Findings caused solely by demonstration certificate or seal values are shown as expected warnings. `DOMDocument::schemaValidate()` remains the command's pass/fail structural gate.

## Phases

### 1. Bootstrap and local validation assets — complete

- Create the Laravel application in `laravel/` and root Docker environment.
- Trim unused Laravel web, database, and development defaults.
- Store the official `cfdv40.xsd` and its local SAT dependencies under `laravel/resources/xsd/`.
- Verify the Docker image can install dependencies and expose `bcmath`, DOM, and libxml.

### 2. Input contract and calculation domain — complete

- Define the JSON contract and explicit input/business checks.
- Calculate concept amounts, IVA, subtotal, transferred taxes, and total using `bcmath`.
- Add the type dispatcher and the `Ingreso` handler.

### 3. CFDI XML generation — complete

- Build the `Ingreso` XML with `DOMDocument` and required CFDI 4.0 nodes.
- Add XSD-required demonstration `Sello`, `NoCertificado`, and `Certificado` values; document that they are not legal or signed values.

### 4. Local XSD validation — complete

- Validate generated XML offline with `DOMDocument::schemaValidate()`.
- Capture libxml errors with line, column, and message.
- Configure CfdiUtils with local SAT resources and report its applicable CFDI assertions separately from structural XSD validity.

### 5. Artisan command and artifact — complete

- Add `cfdi:40:generate`, fixed output, clear summaries, and non-zero failure statuses.
- Generate and commit the example XML.

### 6. Verification and documentation — complete

- Add behavior-focused PHPUnit coverage for totals, successful generation/validation, and failures.
- Polish the README, run Pint and tests through Docker, and generate the example XML.
