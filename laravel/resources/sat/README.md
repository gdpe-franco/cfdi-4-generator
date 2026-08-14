# SAT Catalog Resource

The read-only SQLite catalog database is published by [PhpCfdi resources-sat-catalogs](https://github.com/phpcfdi/resources-sat-catalogs) and installed at `storage/app/sat/catalogs.db`.

Its pinned source URL and SHA-256 are defined in `App\\Services\\Cfdi\\V40\\Schema`. Install it with `php artisan cfdi:catalogs:install`; use `--update` only after intentionally changing those pinned values.

The application reads it through `phpcfdi/sat-catalogos`. It is not an application database and must not be modified at runtime.
