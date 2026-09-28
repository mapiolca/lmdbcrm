# Permission regression tests

Run from the module root with PHP 8.0+ and an unmodified Dolibarr checkout:

```sh
LMDBCRM_CORE_SOURCE=/path/to/dolibarr/htdocs LMDBCRM_CORE_VERSION=20.0.0 php test/run.php
```

PowerShell:

```powershell
$env:LMDBCRM_CORE_SOURCE = 'C:/path/to/dolibarr/htdocs'
$env:LMDBCRM_CORE_VERSION = '20.0.0'
php test/run.php
```

The harness copies the selected native `ModeleBoxes` and `DolibarrModules` into an isolated directory under `.test-cache`. It executes the real renderer and permission-registration method. Session, date, SQL, language and filesystem helper implementations are test doubles; the suite does not load an instance configuration or use real users. Warnings fail tests; native deprecations from older Dolibarr versions on newer PHP are excluded.

Coverage includes all seven widgets, all four permission combinations, independent ranking rights, the ranking entry point in subprocesses, native menu conditions, direct load/render calls, module/native-right/external-user denials, repeated loads, stale-data downgrades, cache failures, and configuration restoration. Debug parameters are deliberately enabled.

## Real SQL and registration

`php test/sql.php` additionally requires `pdo_mysql`, `LMDBCRM_TEST_DSN`, `LMDBCRM_TEST_USER` and `LMDBCRM_TEST_PASSWORD`, targeting an **empty disposable MariaDB database**. It creates only `test_*` tables and refuses existing tables. Do not point it at an instance database.

The test executes the widgets' generated SQL against distinguishable customer/entity fixtures and checks aggregate results. It also executes native box/right registration and disable/reactivate cycles through a PDO test adapter. Unrelated activation side effects (menus, module constants, directories, hooks and cron) are isolated. This validates real MariaDB statements, not a complete Dolibarr installation or the live Multicompany plugin.

CI runs both suites with Dolibarr 20.0.0 / 24.0.0 and PHP 8.0 / 8.4. No dependency is installed into a host Dolibarr instance.

## Manual acceptance on a deployed test instance

- Grant rights directly and through different groups; verify full-read precedence and no administrator bypass when `hasRight()` is false.
- Inspect the ranking URL, menu, widget catalogue and previously placed widgets with no rights, preview-only and full read.
- Verify previews on desktop/mobile, native move/close controls, no real identities in selectors, and no protected values in HTML, scripts, tooltips or network responses.
- Enable the native file cache; render full data, revoke full access, reload native permissions and verify preview/denial immediately. Repeat after reducing customer assignments or entity sharing.
- Check actual Multicompany global/individual sharing on the installed plugin version and confirm the served commit before reporting browser results.
- Reactivate the module twice and compare user/group assignments and widget positions.

No production instance, browser or Multicompany installation is validated by the automated harness alone.
