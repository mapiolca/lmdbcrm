<?php
/** Regression: historical native module name must work for direct and inherited grants. */
require __DIR__.'/bootstrap.php';
require __DIR__.'/nativeuser.php';
// Bind the fixture to the selected native descriptor, not the module's loading argument.
$descriptorSource = file_get_contents($coreSource.'/core/modules/modPropale.class.php');
if (!preg_match('/\$this->rights_class\s*=\s*[\'\"]propale[\'\"]/', $descriptorSource)) {
	throw new RuntimeException('Review the proposal rights fixture against the changed native descriptor');
}
class ProposalRightsDb extends DoliDB
{
	public function query($sql, $ignore = 0)
	{
		if (strpos($sql, 'SELECT DISTINCT r.module') !== 0) return parent::query($sql, $ignore);
		$this->queries[] = $sql;
		$correctModule = strpos($sql, "r.module = 'propale'") !== false;
		$direct = strpos($sql, 'ur.fk_user = 7') !== false;
		$group = strpos($sql, 'gu.fk_user = 8') !== false;
		return new TestResult($correctModule && ($direct || $group)
			? array((object) array('module' => 'propale', 'perms' => 'creer', 'subperms' => null, 'entity' => 1)) : array());
	}
}
$db = new ProposalRightsDb();
$candidateRightsLoader = function ($id, $module) use ($db) {
	$native = new EligibilityNativeUser();
	$native->db = $db;
	$native->id = $id;
	$native->admin = $id === 9 ? 1 : 0;
	$native->loadRights($module);
	return $native->hasRight('propal', 'creer') ? array('propal.creer') : array();
};
$user->grants[] = 'lmdbcrm.ranking.readall';
ob_start();
require dirname(__DIR__).'/commercial_ranking.php';
ob_end_clean();
if ($eligibleUserIds !== array(7, 8) || $selectedUserArgs[5] !== array(7, 8)) {
	throw new RuntimeException('Direct and group proposal grants must both qualify: '.json_encode($eligibleUserIds));
}
print 'OK: native proposal rights alias, direct/group grants, no administrator bypass; simulated SQL; Dolibarr '.DOL_VERSION.PHP_EOL;
