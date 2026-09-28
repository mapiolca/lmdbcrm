<?php
/** Native DolGraph output for many entities; helpers/session/database remain simulated. */
putenv('LMDBCRM_NATIVE_GRAPH=1');
require __DIR__.'/bootstrap.php';
require $moduleRoot.'/core/boxes/lmdbcrm_graph_signedturnover_entities.php';
function getNonce() { return 'test-nonce'; }
function dol_string_nospecial($value, ...$args) { return preg_replace('/[^a-zA-Z0-9_]/', '', $value); }
function dol_string_unaccent($value) { return $value; }
function dol_getThemeFilePath($file) { return $file === 'theme_vars.inc.php' ? DOL_DOCUMENT_ROOT.'/theme/eldy/'.$file : ''; }
function dol_escape_js($value, ...$args) { return str_replace(array("\\", "'", "<", ">"), array("\\\\", "\\'", "\\x3c", "\\x3e"), (string) $value); }
function dol_string_nohtmltag($value, ...$args) { return strip_tags($value); }
class NativeGraphDb extends DoliDB
{
	public function query($sql, $ignore = 0) {
		$rows = array();
		foreach (range(1, 12) as $id) $rows[] = (object) array('entity' => $id, 'label' => 'Entity '.$id,
			'y' => 2026, 'm' => 9, 'amount' => $id * 100, 'qty' => 1);
		return new TestResult($rows);
	}
}
resetContext();
$conf->theme = 'eldy';
$enabledModules['multicompany'] = true;
$entities['propal'] = implode(',', range(1, 12));
$user->grants[] = 'lmdbcrm.widgets.readall';
$db = new NativeGraphDb();
// Simulate two theme palettes; DolGraph loads and renders them without modification.
$themeDir = DOL_DOCUMENT_ROOT.'/theme/eldy';
mkdir($themeDir, 0777, true);
foreach (array(12, 3) as $paletteSize) {
	$palette = array();
	foreach (range(1, $paletteSize) as $id) $palette[] = array($id * 10, 40, 90);
	file_put_contents($themeDir.'/theme_vars.inc.php', '<?php $theme_datacolor = '.var_export($palette, true).';');
	$box = new lmdbcrm_graph_signedturnover_entities($db);
	$box->box_id = 600;
	$box->loadBox();
	$html = $box->showBox(null, null, 1);
	foreach (range(1, 12) as $id) {
		if (strpos($html, 'Entity '.$id) === false) throw new RuntimeException('Native chart lost entity '.$id);
		if (strpos($html, '#'.substr(hash('sha256', 'lmdbcrm-entity-'.$id), 0, 6)) !== false) throw new RuntimeException('Custom colour still overrides theme');
	}
	foreach ($palette as $rgb) {
		if (strpos($html, 'rgb('.implode(', ', $rgb)) === false) throw new RuntimeException('Native theme colour missing');
	}
	if (strpos($html, '2025') !== false || strpos($html, '2024') !== false) throw new RuntimeException('Historical series exposed');
}
print 'OK: native DolGraph '.DOL_VERSION.' renders 12 entity series with simulated 12-colour and 3-colour themes.'.PHP_EOL;
