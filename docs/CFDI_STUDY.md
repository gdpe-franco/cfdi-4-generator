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
10. Why does `CfdiXmlGenerator` return a `DOMDocument` rather than writing a file itself? Which layer should report write failures to the user?
11. Compare `Ingreso`, `Egreso`, `Traslado`, `Pago`, and `Nómina`. Why does supporting a new `TipoDeComprobante` require more than accepting a new letter?

## Phase 4 — Structural validation

1. Why should `DOMDocument::schemaValidate()` receive the local `cfdv40.xsd` file even when the XML already contains `xsi:schemaLocation`?
2. Why must `libxml_use_internal_errors(true)` be restored after validation? What would be the risk of printing libxml errors directly from the service?
3. What does a libxml line/column/message error tell a command user, and why is it more useful than a simple `false` validation result?
4. Why is a successful XSD result the structural pass/fail gate, while CfdiUtils findings are advisory in this technical test?
5. Why does CfdiUtils need PHP's XSL extension even though our own structural validator only uses DOMDocument and libxml?
6. Why must an offline XML resolver reject a missing resource instead of silently downloading it?

## How to use this guide

- Answer one phase before moving to the next implementation phase.
- Mark uncertain answers with `?` rather than guessing silently.
- Ask for a review with the question numbers and your answers; add follow-up questions only when they reveal a useful gap.
