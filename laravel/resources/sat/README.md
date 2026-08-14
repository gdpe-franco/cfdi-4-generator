# SAT Catalog Resource

`catalogs.db` is the read-only SQLite catalog database published by [PhpCfdi resources-sat-catalogs](https://github.com/phpcfdi/resources-sat-catalogs).

- Upstream release: `v10.13.20260731`
- Source archive: `https://github.com/phpcfdi/resources-sat-catalogs/releases/download/v10.13.20260731/catalogs.db.bz2`
- SHA-256: `eb80704627a8dbf72666d6641b8d93e09a9ab2b9a2b10c30ca22a159a9012ae3`

The application reads this resource through `phpcfdi/sat-catalogos`. It is not an application database and it must not be modified at runtime. Updating it is an explicit dependency update: download a new immutable upstream release, verify its checksum, replace this file, and update this record.
