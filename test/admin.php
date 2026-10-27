<?php
/** Real admin entry points, simulated bootstrap/native helpers, no instance writes. */
require __DIR__.'/bootstrap.php';
file_put_contents(DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php', '<?php // Native helpers supplied by this fixture.');
function complete_head_from_modules(...$args) {}
function dol_get_fiche_head($head, $active, ...$args) {
	$GLOBALS['adminHead'] = array('tabs' => $head, 'active' => $active);
	return '';
}
function dol_get_fiche_end() { return ''; }
function dol_include_once($path) { return false; }

function renderAdminPage($page) {
	global $db, $user, $langs, $conf;
	ob_start();
	try { include dirname(__DIR__).'/admin/'.$page.'.php'; return ob_get_contents(); }
	finally { ob_end_clean(); }
}

$user->admin = 1;
foreach (array('settings'=>'setup', 'compatibility'=>'compatibility', 'about'=>'about') as $tab=>$page) {
	$html = renderAdminPage($page);
	if (strpos($html,'search_keyword=lmdbcrm') === false || strpos($html,'BackToModuleList') === false) throw new RuntimeException('Missing module-list return link');
	if ($adminHead['active'] !== $tab || array_column($adminHead['tabs'], 2) !== array('settings','compatibility','about')) throw new RuntimeException('Admin tabs do not match route');
	$document = new DOMDocument();
	libxml_use_internal_errors(true);
	$document->loadHTML($html);
	libxml_clear_errors();
	$xpath = new DOMXPath($document);
	if ($xpath->query('//table//table')->length !== 0 || $xpath->query('//table')->length !== ($tab === 'about' ? 3 : 2)) throw new RuntimeException('Invalid admin table structure');
	if ($tab === 'about') {
		$descriptor = new modLmdbCrm($db);
		foreach (array($descriptor->version, $descriptor->family, $descriptor->module_license, $descriptor->module_author) as $value) {
			if (strpos($html, dol_escape_htmltag($value)) === false) throw new RuntimeException('About metadata does not match descriptor');
		}
		foreach (array('/lmdbcrm/README.md','/lmdbcrm/ChangeLog.md',$descriptor->module_repository,'https://'.$descriptor->editor_url) as $url) {
			if ($xpath->query('//a[@href="'.$url.'"]')->length !== 1) throw new RuntimeException('Missing About resource link');
		}
		if ($descriptor->config_page_url !== array('setup.php@lmdbcrm')) throw new RuntimeException('More than one module configuration entry');
	}
	if ($tab === 'compatibility' && strpos($html,'LmdbCrmConversionSourceProposal') === false) throw new RuntimeException('Compatibility source is missing');
}

// Both source diagnostics must preserve an unavailable selected source.
$enabledModules['propal'] = false;
foreach (array('setup','compatibility') as $page) {
	$html = renderAdminPage($page);
	if (strpos($html,'NotAvailable') === false || strpos($html,'LmdbCrmConversionUnavailable') === false) throw new RuntimeException('Inactive Proposals not diagnosed');
}
$enabledModules['lmdblead'] = true;
foreach (array('setup','compatibility') as $page) {
	$html = renderAdminPage($page);
	if (strpos($html,'LmdbCrmConversionSourceLead') === false || strpos($html,'LmdbCrmConversionLeadUpdateRequired') === false || strpos($html,'LmdbCrmConversionSourceProposal') !== false) throw new RuntimeException('Unavailable Lead source fell back to Proposals');
}

// All page-specific labels must be supplied in every shipped language.
$pageSource = file_get_contents(dirname(__DIR__).'/admin/setup.php');
preg_match_all("/'(LmdbCrm[A-Za-z]+)'/", $pageSource, $matches);
foreach (array('fr_FR','en_US','de_DE','es_ES','it_IT') as $language) {
	$catalogue = file_get_contents(dirname(__DIR__).'/langs/'.$language.'/lmdbcrm.lang');
	foreach (array_unique($matches[1]) as $key) {
		if (!preg_match('/^'.preg_quote($key, '/').'\s*=\s*\S/mu', $catalogue)) throw new RuntimeException('Missing '.$language.' label '.$key);
	}
}

$user->admin = 0;
foreach (array('setup','about','compatibility') as $page) {
	$denied = false;
	try { renderAdminPage($page); } catch (AccessDenied $e) { $denied = true; }
	if (!$denied) throw new RuntimeException('Non-admin entered '.$page);
}
if ($db->queries !== array()) throw new RuntimeException('Admin metadata must not query business data');
echo "OK: three admin routes, tables, descriptor metadata/resources, five languages, unavailable sources and role checks; simulated native helpers.\n";
