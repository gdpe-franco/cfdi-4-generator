# CFDI Study Guide

Learning notes for explaining this project from both implementation and accounting perspectives. Answer each question in your own words, then compare it with the code and official SAT documentation.

## Official learning path

1. [SAT Factura microsite](https://www.sat.gob.mx/minisitio/Factura/default.htm): official index for CFDI 4.0 guides, catalogs, complements, examples, and simulator.
2. [Formato de Factura / Anexo 20](https://wwwmatnp.sat.gob.mx/consultas/35025/formato-de-factura-electronica-%28anexo-20%29): explains the document format, purpose, legal context, and CFDI 4.0 changes.
3. [SAT technical documentation](https://wwwmat.sat.gob.mx/cs/Satellite?c=ConsultaInfo&childpagename=SatTyR%2FConsultaInfo%2FSAT_LandingConsultaInformacion&cid=1462241943074&packedargs=d%3DTouch&pagename=TySWrapper): provides XSDs, catalogs, the cadena original XSLT, and the CFDI 4.0 error matrix.
4. [SAT CFDI 4.0 simulator](https://wwwmat.sat.gob.mx/aplicacion/80484/simulador-genera-tu-factura-version-4.0): connects fields to the issuance flow.
5. [CfdiUtils documentation](https://cfdiutils.readthedocs.io/): PHP-oriented reading, creation, and validation reference. It is an engineering aid, not the fiscal authority.

Use the sources in this order:

```text
SAT guide → supplied Ingreso example → XSD and catalogs → error matrix
→ CfdiUtils implementation docs → signing, cadena original, PAC timbrado
```

Keep these layers distinct:

```text
SAT guide: meaning and when to fill a field
XSD/catalogs: XML structure and allowed technical values
Error matrix/PAC: additional issuance and certification checks
Tax/accounting rules: correct treatment of the underlying operation
```

## Current validation model

This project uses three implementation layers, in execution order:

1. **Input, catalog, and filling-guide rules:** `InputValidator`, `SatCatalog`, and `IngresoBusinessValidator` reject unsupported input before XML generation. The current `Ingreso` rules include `FormaPago`/`MetodoPago`, the `UsoCFDI`/`RegimenFiscalReceptor` relationship, generic foreign RFC values, and the unsupported `Exportacion=02` scenario.
2. **Official XML/XSD structure:** `XsdValidator` uses the local SAT XSD tree through `DOMDocument::schemaValidate()`. This is a command pass/fail gate, but it does not prove a transaction is fiscally valid.
3. **CfdiUtils supplemental structure:** `AdvisoryValidator` uses the same local resources. Applicable findings are advisory. `SELLO*` and `TFD*` checks are skipped because a real certificate, signature, and `TimbreFiscalDigital` are out of scope; their codes are written to Laravel's log rather than shown as passed or failed certificate checks.

The `phpcfdi/sat-catalogos` SQLite file is read-only reference data, not an application database. Setup installs its pinned upstream release and checksum-defined resource into `storage/app/sat/`. Updating it is a reviewed dependency-resource update, never an XML-generation runtime download.

## Phase 1 — Project boundaries

1. What is the difference between generating a CFDI-shaped XML, validating it with an XSD, signing it with a CSD, and timbrado by a PAC?
2. Why does this project use Laravel instead of raw PHP even though it has no web UI or database?
3. Which PHP capabilities come from the Docker image, and which come from Composer dependencies? Where does `bcmath` belong?
4. Why must SAT XSD files and their imported dependencies be stored locally instead of fetched during validation?

## Phase 2 — Input and calculations

1. Why do monetary calculations use `bcmath` decimal strings instead of PHP floats? Give a concrete float-risk example.
2. Explain the relationship among `Cantidad`, `ValorUnitario`, and a concept's `Importe`. Which values come from input and which are calculated?
3. Explain the relationship among a `Traslado`'s `Base`, `TasaOCuota`, and `Importe`. Calculate the IVA for quantity `2`, unit price `125.50`, and IVA rate `0.160000`.
4. Why does this project preserve six decimals during calculation but use two-decimal document totals? Which values must reconcile when rounding is involved?
5. Why does omitting `tipoDeComprobante` default to `I`, while explicitly sending `E` is rejected?
6. Why is rejecting unknown JSON input fields an application-contract rule rather than a SAT XML rule? What production risk does it avoid?
7. Which validations should happen before XML creation, which belong in XSD validation, and which require a PAC or SAT services?
8. Our first contract permits only IVA rates `0.000000`, `0.080000`, and `0.160000`. What must change before supporting another tax scenario such as retentions or an exempt concept?

## Phase 3 — XML structure

1. What does CFDI mean, and what role does `cfdi:Comprobante` play in the document?
2. Why must all CFDI elements be created in the `http://www.sat.gob.mx/cfd/4` namespace with the `cfdi` prefix?
3. What does `xsi:schemaLocation` communicate? Why does its URL not make our local XSD validation depend on the network?
4. Contrast concept-level `cfdi:Impuestos` with global `cfdi:Impuestos`. What information is lost if only the global node exists?
5. Why must global `cfdi:Traslado` rows be grouped by `Impuesto`, `TipoFactor`, and `TasaOCuota`? Give an example containing 16%, 8%, and 0% IVA concepts.
6. In the supplied fixture, calculate and explain the path from the first concept to its IVA amount: `Cantidad × ValorUnitario = Base`; `Base × TasaOCuota = IVA`.
7. What does XSD stand for? Name four things an XSD can validate and four fiscal/business facts it cannot prove.
8. Why do `Sello`, `NoCertificado`, and `Certificado` exist in a real CFDI? Why do we use placeholders in this technical test, and why must the result never be presented as legally valid?
9. Why is `Fecha` generated using `America/Mexico_City` rather than trusted directly from input in this project?
10. Why does `XmlGenerator` return a `DOMDocument` rather than writing a file itself? Which layer should report write failures to the user?
11. Compare `Ingreso`, `Egreso`, `Traslado`, `Pago`, and `Nómina`. Why does supporting a new `TipoDeComprobante` require more than accepting a new letter?

## Phase 4 — Structural validation

1. Why should `DOMDocument::schemaValidate()` receive the local `cfdv40.xsd` file even when the XML already contains `xsi:schemaLocation`?
2. Why must `libxml_use_internal_errors(true)` be restored after validation? What would be the risk of printing libxml errors directly from the service?
3. What does a libxml line/column/message error tell a command user, and why is it more useful than a simple `false` validation result?
4. Why is a successful XSD result the structural pass/fail gate, while CfdiUtils findings are advisory in this technical test?
5. Why does CfdiUtils need PHP's XSL extension even though our own structural validator only uses DOMDocument and libxml?
6. Why must an offline XML resolver reject a missing resource instead of silently downloading it?

## Phase 5 — Command and artifact

1. Why does `cfdi:40:generate` use `40` rather than `4.0` in its Artisan name, while the XML attribute remains `Version="4.0"`?
2. Why does this project deliberately write to one fixed path, `storage/app/cfdi/cfdi.xml`? What operating risk would need a different policy in a multi-user or concurrent system?
3. Trace the command flow from JSON file to terminal result. Which component owns input validation, calculations, XML construction, disk writing, XSD validation, and advisory findings?
4. Why does the command return a non-zero status for invalid input or failed XSD validation, but not for an expected demonstration `SELLO01` warning?
5. If an XSD error reports a line and column, what should you inspect first in the generated XML and in the relevant XSD definition?

## Phase 6 — Delivery and verification

1. Why is the generated `cfdi.xml` committed even though `storage/app/` is normally ignored by Laravel projects?
2. What does the command’s successful XSD result prove? List three facts that it still cannot prove about a real fiscal transaction.
3. Explain how `make setup` and the other Make commands make the project reproducible for someone who has Docker but no local PHP installation.
4. Why do the tests cover concise happy and failure paths rather than attempting to reproduce every SAT catalog and PAC rule?

## Phase 7 — SAT catalogs and cross-field rules

1. Why is the full SAT catalog resource installed as a read-only SQLite file rather than copied into PHP arrays, JSON, or an application database?
2. Explain the separate responsibilities of `CfdiUtils`, `phpcfdi/sat-catalogos`, the SAT catalog resource, and `IngresoBusinessValidator`.
3. Why does XSD validation accept a code such as `G01` without necessarily proving it is compatible with the receiver's `RegimenFiscalReceptor`?
4. Explain why `FormaPago=99` and `MetodoPago=PPD` must appear together for this supported `Ingreso` model. What later document is expected when payment is received?
5. Why is `Exportacion=02` rejected here even though it is an existing catalog value? What is the difference between a valid catalog code and a supported document scenario?
6. Which facts about `XEXX010101000` can be checked locally, and which still require SAT/PAC services or taxpayer records?
7. Why must a catalog update be a reviewed, version-pinned setup update rather than an XML-generation runtime download?

## Phase 8 — Type-specific dispatch

1. Why does `InputValidator` default `TipoDeComprobante` to `I` but leave support decisions to the dispatchers?
2. When adding `Egreso` (`E`), which three implementation pieces must be added or changed: its business validator, its calculator, and which dispatcher branches?
3. Why should an `Egreso` input contract be extended deliberately instead of assuming every `Ingreso` field and rule applies unchanged?
4. What is the difference between a type-specific business-validation failure and an XSD validation failure?

## Phase 9 — Delivery and future integrations

`make setup` is the repository entrypoint. It builds Docker, then runs Composer's Laravel-level `setup` script: dependency installation, `.env` creation when missing, application-key generation, configuration cleanup, and verified local catalog installation. Composer owns application bootstrap; Make owns the repeatable host command.

CfdiUtils is already installed but deliberately used only for supplemental structure checks. Its wider scope includes CFDI creation and reading, local XSD validation, `cadena de origen`, certificate helpers, `Sello` validation, and `TimbreFiscalDigital` validation. `phpcfdi/sat-catalogos` supplies local catalog lookup objects; it does not perform PAC operations or manage catalog updates.

InvoiceOne is a possible future PAC integration. Its published SOAP services cover timbrado, cancellation, and stamped XML retrieval. A real integration still needs a selected contract, provider credentials, protected CSD/PFX handling, retries and idempotency, persistence/audit controls, and an explicit cancellation workflow.

1. Why is `make setup` the public project command while `composer setup` remains necessary underneath it?
2. Which pieces of `composer setup` are Laravel bootstrap, and which one is CFDI-specific?
3. Name three CfdiUtils capabilities that are intentionally not enabled here. Why does using the library not make the XML legally valid?
4. What does `phpcfdi/sat-catalogos` provide, and what does it deliberately not provide?
5. Why must a PAC integration be designed around its actual provider contract instead of treating timbrado as one generic HTTP call?
6. Why are CSD/PFX storage, idempotency, and audit records essential before a production timbrado or cancellation feature?

## How to use this guide

- Answer one phase before moving to the next implementation phase.
- Mark uncertain answers with `?` rather than guessing silently.
- Ask for a review with the question numbers and your answers; add follow-up questions only when they reveal a useful gap.
