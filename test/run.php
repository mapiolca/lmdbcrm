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
		$user->grants[] = 'lmdbcrm.ranking.readall'; // Independent of widget rights.
		if ($rights & 1) $user->grants[] = 'lmdbcrm.widgets.read';
		if ($rights & 2) $user->grants[] = 'lmdbcrm.widgets.readall';
		$box = new $class($db);
		$box->box_id = 100 + $index;
		check($box->hidden === ($rights === 0), $class.' catalogue visibility');
		$cache = oldCache($box, 'SECRET_OLD_CACHE');
		$debug = captureLoad($box);
		$html = $box->showBox(null, null, 1);
		check(strpos($html, 'SECRET_OLD_CACHE') === false, $class.' stale cache');
		check(getDolGlobalInt('MAIN_ACTIVATE_FILECACHE') === 1, $class.' restored cache setting');
		if ($rights === 0) {
			check(!$db->queries && $debug === '' && $html === '', $class.' denied without queries or output');
		} else {
			check(count($db->queries) > 0 && $html !== '', $class.' authorised load');
			check(!file_exists($cache), $class.' stale cache invalidated in both scopes');
			check(strpos($html, 'id="boxto_'.$box->box_id.'"') !== false && strpos($html, 'id="imgclose'.$box->box_id.'"') !== false, $class.' native move/close identity');
			if ($rights === 1) check($debug === '', $class.' personal mode ignores diagnostics');
			foreach ($db->queries as $sql) {
				check(strpos($sql, 'entity IN (1)') !== false, $class.' entity scope');
				check(strpos($sql, 'sc.fk_user = 7') !== false, $class.' native commercial scope');
				if ($rights === 1 && strpos($class, 'graph_') !== false) check(strpos($sql, 'p.fk_user_author = 7') !== false, $class.' personal series only');
			}
		}
	}
	// Downgrade an already loaded object and an existing full cache, without reloading.
	$user->grants = array('propal.lire', 'commande.lire', 'lmdbcrm.widgets.read');
	$box->info_box_head = array('text' => 'SECRET_HEADER');
	$box->info_box_contents = array(array(array('text' => 'SECRET_RECORD')));
	oldCache($box, 'SECRET_OLD_CACHE');
	$before = count($db->queries);
	$html = $box->showBox(null, null, 1);
	check($html === '', $class.' downgrade refuses data loaded with previous scope');
	check(count($db->queries) === $before, $class.' showBox does not reload implicitly');
	check(!$box->info_box_head && !$box->info_box_contents, $class.' sensitive memory cleared');
	check(captureLoad($box) === '' && captureLoad($box) === '', $class.' repeated personal load');
	check(strpos($box->showBox(null, null, 1), 'SECRET') === false, $class.' personal reload has no old content');
	$user->grants = array('propal.lire', 'commande.lire');
	check($box->showBox(null, null, 1) === '', $class.' preview to denied');

	foreach (array('native', 'external', 'module', 'dependency') as $denial) {
		resetContext();
		$user->grants[] = 'lmdbcrm.widgets.readall';
		$user->grants[] = 'lmdbcrm.widgets.read';
		$source = strpos($class, 'orders_') !== false ? 'commande' : 'propal';
		if ($denial === 'native') $user->grants = array('lmdbcrm.widgets.readall', 'lmdbcrm.widgets.read');
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
	$user->grants[] = 'lmdbcrm.widgets.readall';
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
check(array_column(array_values($descriptor->rights), 5) === array('read', 'readall', 'read', 'readall'), 'requested permission order');
foreach ($descriptor->rights as $offset => $right) {
	check($offset >= 5 && $offset <= 8 && $right[0] === 45001100 + $offset && $right[3] === 0, 'stable opt-in right; legacy offsets reserved');
}
foreach (array(0, 1, 2, 3) as $rights) {
	$user->grants = array('propal.lire', 'lmdbcrm.widgets.readall');
	if ($rights & 1) $user->grants[] = 'lmdbcrm.ranking.read';
	if ($rights & 2) $user->grants[] = 'lmdbcrm.ranking.readall';
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
		check(strpos($output, 'LmdbCrmOwnRankingNotice') !== false && strpos($output, 'CASE WHEN u.rowid = 7') !== false, 'ranking personal scope');
		check(strpos($output, 'REAL_USER_SELECTOR') === false, 'ranking no identity selector');
	} else {
		check(strpos($output, 'ACCESS_DENIED') !== false && strpos($output, 'QUERIES=[]') !== false, 'ranking denied before SQL');
	}
}
foreach (array('ranking', 'lmdbcrm_podium_signedquotes', 'lmdbcrm_podium_signedturnover', 'lmdbcrm_graph_conversionrates', 'lmdbcrm_graph_marginrates', 'lmdbcrm_graph_signedquotes', 'lmdbcrm_graph_signedturnover') as $target) {
	$process = proc_open(array(PHP_BINARY, __DIR__.'/personal.php', $target), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
	$output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
	fclose($pipes[1]); fclose($pipes[2]);
	check(proc_close($process) === 0 && $error === '', $target.' populated personal render: '.$error);
	check(strpos($output, 'OK populated') !== false, $target.' personal assertions completed');
}
foreach (array('ranking', 'lmdbcrm_podium_signedquotes', 'lmdbcrm_podium_signedturnover') as $target) {
	$process = proc_open(array(PHP_BINARY, __DIR__.'/personal.php', $target, 'all'), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
	$output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
	fclose($pipes[1]); fclose($pipes[2]);
	check(proc_close($process) === 0 && $error === '', $target.' full read precedence: '.$error);
}
print 'OK: '.$checks.' checks; native Dolibarr '.DOL_VERSION.' renderer/permissions; simulated session, SQL and cache helpers.'.PHP_EOL;
