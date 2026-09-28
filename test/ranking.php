<?php
require __DIR__.'/bootstrap.php';
$scenario = $argv[1] ?? 'none';
if (in_array($scenario, array('masked', 'both'), true)) $user->grants[] = 'lmdbcrm.ranking.read';
if (in_array($scenario, array('full', 'both', 'external', 'disabled', 'native-denied'), true)) $user->grants[] = 'lmdbcrm.ranking.readall';
if (strpos($scenario, 'sql-') === 0) {
	$user->grants[] = 'lmdbcrm.ranking.readall';
	if (in_array($scenario, array('sql-expanded', 'sql-shared'), true)) $user->grants[] = 'societe.client.voir';
	if (strpos($scenario, 'sql-shared') === 0) {
		$conf->entity = 2;
		$entities = array('propal' => '1,2', 'user' => '1,2');
	}
}
if ($scenario === 'widgets-only') $user->grants[] = 'lmdbcrm.widgets.readall';
if ($scenario === 'external') $user->socid = 99;
if ($scenario === 'disabled') $enabledModules['lmdbcrm'] = false;
if ($scenario === 'native-denied') $user->grants = array('lmdbcrm.ranking.readall');
$user->admin = 1; // Never bypass hasRight(), even for this account.
register_shutdown_function(function () use ($db) {
	print "\nQUERIES=".json_encode($db->queries)."\n";
});
try {
	require dirname(__DIR__).'/commercial_ranking.php';
} catch (AccessDenied $e) {
	print $e->getMessage();
}
