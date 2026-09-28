<?php
/** Render populated fixtures with deliberately distinctive third-party secrets. */
require __DIR__.'/bootstrap.php';
$allowTestGraphs = true;
class PersonalRenderDb extends DoliDB
{
	public function query($sql, $ignore = 0)
	{
		$this->queries[] = $sql;
		if (strpos($sql, 'as userid') !== false) {
			$rows = array();
			foreach (array(8, 9, 10, 7) as $id) {
				$own = $id === 7;
				$rows[] = (object) array('userid' => $id, 'lastname' => $own ? 'OWN_USER' : 'SECRET_USER', 'firstname' => '', 'login' => $own ? 'OWN_USER' : 'SECRET_USER', 'email' => 'SECRET_EMAIL', 'photo' => '', 'statut' => 1, 'qty' => $own ? 42 : 994321, 'amount' => $own ? 42 : 994321, 'total_count' => $own ? 42 : 994321, 'signed_count' => $own ? 42 : 994321, 'total_amount' => $own ? 42 : 994321, 'signed_amount' => $own ? 42 : 994321, 'conversion_rate' => $own ? 42 : 994321);
			}
			return new TestResult($rows);
		}
		$own = strpos($sql, 'p.fk_user_author = 7') !== false;
		$value = $own ? 42 : 994321;
		return new TestResult(array((object) array('y' => 2026, 'm' => 9, 'nb' => $value, 'amount' => $value, 'turnover' => $value, 'cost' => 21, 'total' => $value, 'signed' => 21)));
	}
}
$db = new PersonalRenderDb();
$user->grants[] = 'lmdbcrm.ranking.read';
$user->grants[] = 'lmdbcrm.widgets.read';
$user->grants[] = 'societe.client.voir';
$_GET['search_user'] = array(8);
$_GET['search_user_keyword'] = 'SECRET_USER';
$_GET['sortfield'] = 'total_amount';
$target = $argv[1] ?? 'ranking';
ob_start();
if ($target === 'ranking') {
	require dirname(__DIR__).'/commercial_ranking.php';
} else {
	require dirname(__DIR__).'/core/boxes/'.$target.'.php';
	$box = new $target($db);
	$box->box_id = 99;
	$box->loadBox();
	print $box->showBox(null, null, 1);
}
$html = ob_get_clean();
if (strpos($html, 'SECRET') !== false || strpos($html, '994321') !== false || strpos($html, 'REAL_USER_SELECTOR') !== false) throw new RuntimeException($target.' exposed other users');
if (strpos($html, '42') === false && strpos($html, 'test-graph') === false) throw new RuntimeException($target.' lost own values');
if ($target === 'ranking' || strpos($target, 'podium') !== false) {
	if (strpos($html, 'OWN_USER') === false || strpos($html, '>4<') === false) throw new RuntimeException($target.' lost own fourth position');
	if (strpos($html, 'LmdbCrmOtherSalesRep') === false) throw new RuntimeException($target.' lost anonymous ranking');
}
print 'OK populated personal rendering: '.$target.PHP_EOL;
