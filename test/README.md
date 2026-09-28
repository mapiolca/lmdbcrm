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

Coverage includes all eight widgets, all four permission combinations, independent ranking rights, the ranking entry point in subprocesses, native menu conditions, direct load/render calls, module/native-right/external-user denials, repeated loads, stale-data downgrades, cache failures, and configuration restoration. Empty podiums and SQL failures are rendered through the native renderer in personal/full modes: a single message cell must span the three columns, including after repeated loads. Language calls remain simulated; the empty message uses the native `NoRecordFound` key. Debug parameters are deliberately enabled. Populated fixtures verify the viewer’s fourth position outside the podium, their own clear values and the absence of third-party secrets, including forged identity filters.

## Real SQL and registration

`php test/sql.php` additionally requires `pdo_mysql`, `LMDBCRM_TEST_DSN`, `LMDBCRM_TEST_USER` and `LMDBCRM_TEST_PASSWORD`, targeting an **empty disposable MariaDB database**. It creates only `test_*` tables and refuses existing tables. Do not point it at an instance database.

The test executes the ranking and widgets' generated SQL against distinguishable customer/entity fixtures and checks aggregate results. It also executes native box/right registration and disable/reactivate cycles through a PDO test adapter. Legacy user/group rights, entity-scoped migration, repeated activation and durable revocation are checked. Unrelated activation side effects (menus, module constants, directories, hooks and cron) are isolated. This validates real MariaDB statements, not a complete Dolibarr installation or the live Multicompany plugin.

CI targets Dolibarr 20.0.0 with PHP 8.0/8.4, and 21.0.0, 22.0.0, 23.0.0, 24.0.0 and 25.0.0-alpha with PHP 8.4. Immutable source commits are listed in `.github/workflows/php.yml`; v25 uses development commit `2d2e5779a8e6b036d09383987e2d5ef2a49a8ff2`, not a final release. No dependency is installed into a host Dolibarr instance.

## Manual acceptance on a deployed test instance

- Grant rights directly and through different groups; verify full-read precedence and no administrator bypass when `hasRight()` is false.
- Inspect the ranking URL, menu, widget catalogue and previously placed widgets with no rights, personal-only and full read.
- Verify previews on desktop/mobile, native move/close controls, no other identities in personal selectors, and no protected values in HTML, scripts, tooltips or network responses.
- Enable the native file cache; render full data, revoke full access, reload native permissions and verify personal/denied rendering immediately. Repeat after reducing customer assignments or entity sharing.
- Check actual Multicompany global/individual sharing on the installed plugin version and confirm the served commit before reporting browser results.
- Reactivate the module twice and compare user/group assignments and widget positions.

No production instance, browser or Multicompany installation is validated by the automated harness alone.

## Multi-entity turnover coverage

`test/entities.php` runs within `test/run.php`: rights matrix, native rights, external users, missing module/sharing, one query for the current fiscal year, twelve entities, missing months, zero amounts, shifted fiscal year, empty/error states, repeated loads, cache invalidation and sharing/permission changes before rendering. SQL and graphs are simulated in this suite.

`php test/nativegraph.php` separately executes the unmodified native DolGraph and ModeleBoxes classes for twelve series and checks every legend/colour, on Dolibarr 20.0.0 and 24.0.0. Session, theme and utility helpers remain simulated; this is generated HTML/JavaScript validation, not a browser test. The CI checkout includes `htdocs/core/class` for this test.

`test/sql.php` executes the per-entity aggregation against MariaDB: independent entity totals, restricted/personal access, an empty shared entity and exclusion of an unshared entity. Eight catalogue definitions and seven unchanged default placements are checked through native registration.

Source inspection: Multicompany 24.0.2, local commit `44e62e9`, `sql/llx_entity.sql` (`rowid`, `label`) and `ActionsMulticompany::getEntity()` confirm the entity-label schema and proposal-sharing scope. This is not a live test of the plugin, nor proof of all older Multicompany versions. On a deployed test instance, verify the installed plugin’s sharing configuration, fiscal starting month, entity names, legend readability with many entities, mobile rendering, and that only the current fiscal year is shown.

## Native menu evaluator

`php test/menu.php` extracts and executes the unmodified `dol_eval()` / `dol_eval_standard()` functions from the selected core source, with simulated session/helpers. The old `empty($user->socid)` expression is rejected by the default Dolibarr 24.0.0 allowlist despite granted rights. The corrected expression checks only native/CRM rights; native `user => 0` still restricts the menu to internal users, and the page independently rejects external users. The test covers 32 combinations of internal `socid` values, personal/full rights and the native proposal right, including an administrator without functional grants. This is not a live menu-manager/browser test.

## Permanent ranking eligibility

`eligibility_sql.php`, included by `sql.php`, renders the actual ranking page against MariaDB and executes unchanged native `User::loadRights()` and `User::hasRight()` methods extracted from each selected core revision. Cases cover direct/group grants, a foreign-entity grant, inactive/external users, foreign-origin transverse membership, global users, an administrator without proposal creation rights, group revocation, forged selector values, personal anonymity and the empty state. SQL filters and ranking rendering execute; the form and Multicompany access service are doubles. This does not validate a deployed instance or the complete native selector/Multicompany lifecycle.

Source inspection: Multicompany 24.0.2 (`44e62e9`), `DaoMulticompany::verifyRight()` checks global users, home-entity access and current-entity group membership in transverse mode. Functional permission checks remain separate. Candidate rights are loaded using the core API for each candidate (two permission queries per user); the SQL preselection excludes inactive/external users before these calls. Confirm response time on large user directories, real native selector contents and the installed Multicompany version during acceptance testing.

### Proposal permission storage name regression

`php test/proposalrights.php` renders the actual page and executes unchanged native `loadRights()` / `hasRight()` methods with simulated direct/group grant queries. The fixture uses `rights_def.module = propale`, verified against `modPropale::rights_class` in the selected core descriptor. The loader argument from the page is forwarded unchanged to the native method. Before correction this test returns no eligible users; after correction both the direct and group-granted users qualify, while an administrator without grants remains excluded. The MariaDB suite uses native creation right ID 22 and the same historical module name, with real user/group grant joins. The previous fixture incorrectly stored `propal` and ignored the page's loader argument; it could not detect this defect.
