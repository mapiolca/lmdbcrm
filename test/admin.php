<?php
/** Real admin entry points, simulated bootstrap/native helpers, no instance writes. */
require __DIR__.'/bootstrap.php';
file_put_contents(DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php', '<?php // Native helpers supplied by this fixture.');
function dolBuildUrl($url) { return $url; }
function complete_head_from_modules(...$args) {}
function dol_get_fiche_head($head, $active, ...$args) { return json_encode(array($head, $active)); }
function dol_get_fiche_end() { return ''; }
$user->admin = 1;
foreach (array('settings'=>'setup', 'compatibility'=>'compatibility', 'about'=>'about') as $tab=>$page) {
	unset($lmdbcrmAdminTab);
	ob_start(); include dirname(__DIR__).'/admin/'.$page.'.php'; $html = ob_get_clean();
	if (strpos($html,'BackToModuleList') === false || strpos($html,'compatibility.php') === false || strpos($html,'about.php') === false) throw new RuntimeException('Missing native admin navigation');
	if ($tab === 'about' && (strpos($html,'1.6.0') === false || strpos($html,'GPL-3.0-or-later') === false)) throw new RuntimeException('About metadata does not match descriptor');
	if ($tab === 'compatibility' && strpos($html,'LmdbCrmConversionSourceProposal') === false) throw new RuntimeException('Compatibility source is missing');
}
$user->admin = 0;
foreach (array('setup','about','compatibility') as $page) {
	$denied = false; ob_start();
	try { include dirname(__DIR__).'/admin/'.$page.'.php'; } catch (AccessDenied $e) { $denied = true; } finally { ob_end_clean(); }
	if (!$denied) throw new RuntimeException('Non-admin entered '.$page);
}
if ($db->queries !== array()) throw new RuntimeException('Admin metadata must not query business data');
echo "OK: admin navigation, descriptor metadata, source and role checks; simulated native helpers.\n";
