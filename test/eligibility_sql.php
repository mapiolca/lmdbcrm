<?php
/** Included by sql.php: real page SQL and native rights; Multicompany access is simulated. */
$nativeUserMethods = '';
$nativeTokens = token_get_all(file_get_contents($coreSource.'/user/class/user.class.php'));
foreach (array('loadRights', 'hasRight') as $methodName) {
	$found = false;
	for ($i = 0; $i < count($nativeTokens); $i++) {
		if (!is_array($nativeTokens[$i]) || $nativeTokens[$i][0] !== T_FUNCTION) continue;
		$j = $i + 1;
		while (is_array($nativeTokens[$j]) && $nativeTokens[$j][0] === T_WHITESPACE) $j++;
		if (!is_array($nativeTokens[$j]) || $nativeTokens[$j][1] !== $methodName) continue;
		$depth = 0; $started = false;
		for (; $i < count($nativeTokens); $i++) {
			$token = $nativeTokens[$i];
			$nativeUserMethods .= is_array($token) ? $token[1] : $token;
			if ($token === '{') { $depth++; $started = true; }
			if ($token === '}') $depth--;
			if ($started && $depth === 0) break;
		}
		$found = true;
		break;
	}
	if (!$found) throw new RuntimeException('Native User method missing: '.$methodName);
}
// Execute unchanged native methods with a PDO adapter and explicit fixture properties.
eval('class EligibilityNativeUser { public $db; public $id; public $admin = 0; public $rights; public $nb_rights = 0; public $_tab_loaded = array(); public $all_permissions_are_loaded = 0; '.$nativeUserMethods.' }');
class DaoMulticompany
{
	public function __construct($db) {}
	public function verifyRight($entity, $userid) { return $GLOBALS['entityAccessFixture'][$entity][$userid] ?? 0; }
}
function dol_include_once($path) { return 1; }
function renderEligibleRanking()
{
	global $conf, $db, $langs, $user;
	ob_start();
	try {
		require dirname(__DIR__).'/commercial_ranking.php';
		return array(ob_get_contents(), $eligibleUserIds);
	} finally {
		ob_end_clean();
	}
}

$pdo->exec('ALTER TABLE test_user ADD fk_soc int NULL');
$pdo->exec('CREATE TABLE test_usergroup_user (fk_user int, fk_usergroup int, entity int)');
$pdo->exec('DELETE FROM test_user');
// direct, group, inactive, foreign, external, no right, global, transverse, foreign right
foreach (array(101,102,103,104,105,106,107,108,109) as $fixtureId) {
	$fixtureEntity = in_array($fixtureId, array(104,108), true) ? 2 : ($fixtureId === 107 ? 0 : 1);
	$fixtureStatus = $fixtureId === 103 ? 0 : 1;
	$fixtureSoc = $fixtureId === 105 ? 10 : 0;
	$pdo->exec("INSERT INTO test_user VALUES ($fixtureId,'Sales$fixtureId','','sales$fixtureId','','',$fixtureStatus,$fixtureEntity,$fixtureSoc)");
}
$pdo->exec("INSERT INTO test_rights_def (id, entity, module, perms, subperms) VALUES (21,1,'propal','creer',NULL),(21,2,'propal','creer',NULL)");
foreach (array(101,103,104,105,107,108) as $fixtureId) $pdo->exec('INSERT INTO test_user_rights VALUES ('.$fixtureId.',21,1)');
$pdo->exec('INSERT INTO test_user_rights VALUES (109,21,2)');
$pdo->exec('INSERT INTO test_usergroup_rights VALUES (50,21,1)');
$pdo->exec('INSERT INTO test_usergroup_user VALUES (102,50,1),(108,51,1),(109,50,2)');
resetContext();
$db = new RegistrationDb($pdo);
$candidateRightsLoader = function ($id) use ($db) {
	$native = new EligibilityNativeUser();
	$native->db = $db;
	$native->id = $id;
	$native->admin = $id === 106 ? 1 : 0;
	$native->loadRights('propal');
	return $native->hasRight('propal', 'creer') ? array('propal.creer') : array();
};
$user->grants[] = 'lmdbcrm.ranking.readall';
$entities['user'] = '1,2'; // Sharing users does not grant access to the current entity.
$_GET['search_user'] = array(103,104,105,106,109);
list($eligibleHtml, $eligibleIds) = renderEligibleRanking();
sort($eligibleIds);
sqlCheck($eligibleIds === array(101,102,107), 'active internal users, current access and direct/group current-entity rights');
sqlCheck($selectedUserArgs[0] === array() && $selectedUserArgs[5] === $eligibleIds, 'forged selections removed and native selector restricted');
foreach (array(103,104,105,106,108,109) as $excludedId) sqlCheck(strpos($eligibleHtml, 'sales'.$excludedId) === false, 'excluded identity absent '.$excludedId);
sqlCheck(strpos($eligibleHtml, 'sales102') !== false, 'group-granted sales representative rendered');

$enabledModules['multicompany'] = true;
$conf->global->MULTICOMPANY_TRANSVERSE_MODE = 1;
$entityAccessFixture = array(1 => array(101 => 1,102 => 1,107 => 1,108 => 1));
list($eligibleHtml, $eligibleIds) = renderEligibleRanking();
sort($eligibleIds);
sqlCheck($eligibleIds === array(101,102,107,108), 'transverse group membership plus native access admits foreign-origin user');
$entityAccessFixture[1][101] = 0;
list($eligibleHtml, $eligibleIds) = renderEligibleRanking();
sqlCheck(!in_array(101, $eligibleIds, true), 'native entity-access denial overrides user home entity');

// Rights and access are reloaded on each request, without restoring previous eligibility.
$pdo->exec('DELETE FROM test_usergroup_rights WHERE fk_usergroup=50');
list($eligibleHtml, $eligibleIds) = renderEligibleRanking();
sqlCheck(!in_array(102, $eligibleIds, true), 'group revocation reflected on next ranking');
$user->grants = array('propal.lire', 'lmdbcrm.ranking.read');
$user->id = 108;
list($eligibleHtml, $eligibleIds) = renderEligibleRanking();
sqlCheck(strpos($eligibleHtml, 'sales108') !== false && strpos($eligibleHtml, 'sales107') === false, 'personal ranking retains only own clear identity');
$pdo->exec('DELETE FROM test_user_rights WHERE fk_id=21');
$user->grants[] = 'lmdbcrm.ranking.readall';
list($eligibleHtml, $eligibleIds) = renderEligibleRanking();
sqlCheck($eligibleIds === array() && $selectedUserArgs[5] === array(-1), 'empty eligible set cannot reopen selector');
sqlCheck(strpos($eligibleHtml, 'NoRecordFound') !== false && strpos($eligibleHtml, 'sales') === false, 'native empty state without excluded identities');
unset($candidateRightsLoader);
