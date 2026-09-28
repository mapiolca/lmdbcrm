<?php
require __DIR__.'/bootstrap.php';
require $moduleRoot.'/core/modules/modLmdbCrm.class.php';
foreach (glob($moduleRoot.'/core/boxes/lmdbcrm_*.php') as $file) require $file;

$checks = 0;
function check($condition, $message)
{
	global $checks;
	$checks++;
	if (!$condition) throw new RuntimeException($message);
}
function captureLoad($box)
{
	ob_start();
	try { $box->loadBox(); return ob_get_contents(); } finally { ob_end_clean(); }
}
function oldCache($box, $value)
{
	global $conf, $user;
	$name = '/box-'.get_class($box).'id-'.$box->box_id.'-e'.$conf->entity.'-u'.$user->id.'-s'.$user->socid.'.cache';
	dol_filecache(DOL_DATA_ROOT.'/users/temp/widgets', $name, $value);
	return DOL_DATA_ROOT.'/users/temp/widgets'.$name;
}

$classes = array_map(function ($path) { return basename($path, '.php'); }, glob($moduleRoot.'/core/boxes/lmdbcrm_*.php'));
foreach ($classes as $index => $class) {
	foreach (array(0, 1, 2, 3) as $rights) {
		resetContext();
		$user->admin = 1;
		$user->grants[] = 'lmdbcrm.ranking.read'; // Independent of widget rights.
		if ($rights & 1) $user->grants[] = 'lmdbcrm.widgets.readmasked';
		if ($rights & 2) $user->grants[] = 'lmdbcrm.widgets.read';
		$box = new $class($db);
		$box->box_id = 100 + $index;
		check($box->hidden === ($rights === 0), $class.' catalogue visibility');
		$cache = oldCache($box, 'SECRET_OLD_CACHE');
		$debug = captureLoad($box);
		$html = $box->showBox(null, null, 1);
		check(strpos($html, 'SECRET_OLD_CACHE') === false, $class.' stale cache');
		check(getDolGlobalInt('MAIN_ACTIVATE_FILECACHE') === 1, $class.' restored cache setting');
		if (!($rights & 2)) {
			check(!$db->queries && $debug === '', $class.' no data queries/debug without full right');
			check($rights === 0 ? $html === '' : strpos($html, 'LmdbCrmDataMasked') !== false, $class.' preview/denied output');
			check(strpos($html, 'REAL_USER_SELECTOR') === false && strpos($html, '<canvas') === false, $class.' no real selectors/chart');
		} else {
			check(count($db->queries) > 0 && $html !== '', $class.' full load');
			check(!file_exists($cache), $class.' old full cache removed and not rewritten');
			check(strpos($html, 'LmdbCrmDataMasked') === false, $class.' full takes priority');
			foreach ($db->queries as $sql) {
				check(strpos($sql, 'entity IN (1)') !== false, $class.' entity scope');
				check(strpos($sql, 'sc.fk_user = 7') !== false, $class.' commercial scope');
			}
		}
	}
	// Downgrade an already loaded object and an existing full cache, without reloading.
	$user->grants = array('propal.lire', 'commande.lire', 'lmdbcrm.widgets.readmasked');
	$box->info_box_head = array('text' => 'SECRET_HEADER');
	$box->info_box_contents = array(array(array('text' => 'SECRET_RECORD')));
	oldCache($box, 'SECRET_OLD_CACHE');
	$before = count($db->queries);
	$html = $box->showBox(null, null, 1);
	check(strpos($html, 'SECRET') === false && strpos($html, 'LmdbCrmDataMasked') !== false, $class.' downgrade clears stale data');
	check(count($db->queries) === $before, $class.' preview performs no read');
	check(!$box->info_box_head && !$box->info_box_contents, $class.' sensitive memory cleared');
	check(captureLoad($box) === '' && captureLoad($box) === '', $class.' repeated preview load');
	$user->grants = array('propal.lire', 'commande.lire');
	check($box->showBox(null, null, 1) === '', $class.' preview to denied');

	foreach (array('native', 'external', 'module', 'dependency') as $denial) {
		resetContext();
		$user->grants[] = 'lmdbcrm.widgets.read';
		$user->grants[] = 'lmdbcrm.widgets.readmasked';
		$source = strpos($class, 'orders_') !== false ? 'commande' : 'propal';
		if ($denial === 'native') $user->grants = array('lmdbcrm.widgets.read', 'lmdbcrm.widgets.readmasked');
		if ($denial === 'external') $user->socid = 42;
		if ($denial === 'module') $enabledModules['lmdbcrm'] = false;
		if ($denial === 'dependency') $enabledModules[$source] = false;
		$box = new $class($db);
		$box->box_id = 100 + $index;
		check($box->hidden, $class.' '.$denial.' hidden');
		check(captureLoad($box) === '' && !$db->queries, $class.' '.$denial.' no data');
		check($box->showBox(null, null, 1) === '', $class.' '.$denial.' no render');
	}
	// Shared entities and expanded commercial rights.
	resetContext();
	$user->grants[] = 'lmdbcrm.widgets.read';
	$user->grants[] = 'societe.client.voir';
	$conf->entity = 2;
	$entities = array('propal' => '1,2', 'commande' => '1,2', 'user' => '1,2');
	$box = new $class($db);
	$box->box_id = 100 + $index;
	captureLoad($box);
	foreach ($db->queries as $sql) {
		check(strpos($sql, 'entity IN (1,2)') !== false, $class.' shared scope');
		check(strpos($sql, 'sc.fk_user') === false, $class.' authorised commercial expansion');
	}
	$cache = oldCache($box, 'SECRET_CACHE');
	$failCacheDelete = true;
	check($box->showBox(null, null, 1) === '', $class.' failed invalidation fails closed');
	$failCacheDelete = false;
	$originalGlobal = $conf->global;
	$box->showBox(null, null, 1);
	check($conf->global === $originalGlobal, $class.' exact configuration restored');
	foreach (array(null, '') as $internalSocid) {
		$user->socid = $internalSocid;
		$cache = oldCache($box, 'SECRET_NULL_SOCID_CACHE');
		$html = $box->showBox(null, null, 1);
		check(strpos($html, 'SECRET') === false && !file_exists($cache), $class.' nullable internal socid cache');
	}

}

resetContext();
$descriptor = new modLmdbCrm($db);
check(count($descriptor->rights) === 4, 'four rights');
foreach ($descriptor->rights as $offset => $right) {
	check($right[0] === 45001100 + $offset && $right[3] === 0, 'stable opt-in right');
}
foreach (array(0, 1, 2, 3) as $rights) {
	$user->grants = array('propal.lire', 'lmdbcrm.widgets.read');
	if ($rights & 1) $user->grants[] = 'lmdbcrm.ranking.readmasked';
	if ($rights & 2) $user->grants[] = 'lmdbcrm.ranking.read';
	check((bool) eval('return '.$descriptor->menu[0]['perms'].';') === ($rights !== 0), 'native menu expression');
}
$user->admin = 1;
$db->queries = array();
check($descriptor->insert_permissions(1) === 0, 'native permission registration');
check(count(array_filter($db->queries, function ($sql) { return strpos($sql, 'INSERT INTO test_rights_def') === 0; })) === 4, 'four definitions registered');
check(count(array_filter($db->queries, function ($sql) { return strpos($sql, 'admin = 1') !== false || strpos($sql, 'user_rights') !== false; })) === 0, 'no implicit admin grants');

foreach (array('none', 'masked', 'full', 'both', 'widgets-only', 'external', 'disabled', 'native-denied') as $scenario) {
	$command = array(PHP_BINARY, __DIR__.'/ranking.php', $scenario);
	$process = proc_open($command, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
	$output = stream_get_contents($pipes[1]);
	$error = stream_get_contents($pipes[2]);
	fclose($pipes[1]); fclose($pipes[2]);
	check(proc_close($process) === 0 && $error === '', 'ranking '.$scenario.' process: '.$error);
	if (in_array($scenario, array('full', 'both'), true)) {
		check(strpos($output, 'REAL_USER_SELECTOR') !== false && strpos($output, 'sc.fk_user = 7') !== false, 'ranking full scope');
	} elseif ($scenario === 'masked') {
		check(strpos($output, 'LmdbCrmDataMasked') !== false && strpos($output, 'QUERIES=[]') !== false, 'ranking masked without SQL');
		check(strpos($output, 'REAL_USER_SELECTOR') === false, 'ranking no identity selector');
	} else {
		check(strpos($output, 'ACCESS_DENIED') !== false && strpos($output, 'QUERIES=[]') !== false, 'ranking denied before SQL');
	}
}
print 'OK: '.$checks.' checks; native Dolibarr '.DOL_VERSION.' renderer/permissions; simulated session, SQL and cache helpers.'.PHP_EOL;
