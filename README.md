# CFDI 4.0 Generator

A focused Laravel technical-test project for generating and validating CFDI 4.0 XML documents from structured JSON.

## Status

Phases 1 through 4 are complete. The project provides a Docker-based Laravel command foundation, PHP 8.4 with `bcmath`, XSL, and XML support, local official SAT XSD files, strict `Ingreso` JSON validation, decimal-safe CFDI calculations, programmatic CFDI XML generation, offline XSD validation, and advisory CfdiUtils findings.

## Scope

The first implementation supports CFDI `Ingreso` documents. It will calculate amounts with `bcmath`, build XML with `DOMDocument`, validate it against local SAT schemas, and expose the workflow through an Artisan command.

Real certificates, signatures, PAC integration, and timbrado are intentionally out of scope. Generated XML uses syntactically valid demonstration `Sello`, `NoCertificado`, and `Certificado` values only; it is neither legally valid nor timbrado.

## Development

The Laravel application is in `laravel/`. Run PHP and Composer commands through Docker Compose:

```bash
docker compose run --rm php composer install
docker compose run --rm php php artisan test
```

See [PROJECT.md](PROJECT.md) for the current requirements and delivery phases.

See [CFDI_STUDY.md](docs/CFDI_STUDY.md) for a phased learning guide and official SAT references.
