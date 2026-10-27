<?php
/** Missing/old optional modules must not cause fatal errors or proposal fallback. */
require __DIR__.'/bootstrap.php';
require $moduleRoot.'/class/lmdbcrmconversion.class.php';
function dol_include_once($path) { return true; }
if (($argv[1] ?? '') === 'old') {
	class LmdbLeadCompatibility { public static function isFeatureAvailable($code) { return true; } }
	class LmdbLead_Lead {}
}
$enabledModules['lmdblead'] = true;
$source = LmdbCrmConversion::source();
if ($source['code'] !== 'lead' || $source['available'] || $source['reason'] !== 'LmdbCrmConversionLeadUpdateRequired') throw new RuntimeException('Missing lead contract not diagnosed');
echo "OK: unavailable optional Lead contract ".($argv[1] ?? 'missing').".\n";
