<?php
/** Included by sql.php: real page SQL and native rights; Multicompany access is simulated. */
require_once __DIR__.'/nativeuser.php';
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
$pdo->exec("INSERT INTO test_rights_def (id, entity, module, perms, subperms) VALUES (22,1,'propale','creer',NULL),(22,2,'propale','creer',NULL)");
foreach (array(101,103,104,105,107,108) as $fixtureId) $pdo->exec('INSERT INTO test_user_rights VALUES ('.$fixtureId.',22,1)');
$pdo->exec('INSERT INTO test_user_rights VALUES (109,22,2)');
$pdo->exec('INSERT INTO test_usergroup_rights VALUES (50,22,1)');
$pdo->exec('INSERT INTO test_usergroup_user VALUES (102,50,1),(108,51,1),(109,50,2)');
resetContext();
$db = new RegistrationDb($pdo);
$candidateRightsLoader = function ($id, $module) use ($db) {
	$native = new EligibilityNativeUser();
	$native->db = $db;
	$native->id = $id;
	$native->admin = $id === 106 ? 1 : 0;
	$native->loadRights($module);
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
$pdo->exec('DELETE FROM test_user_rights WHERE fk_id=22');
$user->grants[] = 'lmdbcrm.ranking.readall';
list($eligibleHtml, $eligibleIds) = renderEligibleRanking();
sqlCheck($eligibleIds === array() && $selectedUserArgs[5] === array(-1), 'empty eligible set cannot reopen selector');
sqlCheck(strpos($eligibleHtml, 'NoRecordFound') !== false && strpos($eligibleHtml, 'sales') === false, 'native empty state without excluded identities');
unset($candidateRightsLoader);
