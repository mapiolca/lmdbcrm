<?php
/** Shared-entity widget: native box rendering with simulated SQL and charts. */
class EntityGraphDb extends DoliDB
{
	public $empty = false;
	public $zero = false;
	public function query($sql, $ignore = 0)
	{
		$this->queries[] = $sql;
		if ($this->failQuery) return false;
		$rows = array();
		foreach (range(1, 12) as $id) {
			$rows[] = (object) array('entity' => $id, 'label' => 'Entity <'.$id.'>',
				'y' => $this->empty ? null : 2026, 'm' => $this->empty ? null : 9,
				'qty' => $this->empty ? 0 : 1, 'amount' => $this->zero || $id === 1 ? 0 : $id * 100);
		}
		return new TestResult($rows);
	}
}
foreach (array(0, 1, 2, 3) as $rights) {
	foreach (array('shared', 'single', 'disabled', 'native', 'external', 'module') as $scenario) {
		resetContext();
		$user->admin = 1;
		$enabledModules['multicompany'] = $scenario !== 'disabled';
		$entities['propal'] = $scenario === 'single' ? '1' : '1,2';
		if ($rights & 1) $user->grants[] = 'lmdbcrm.widgets.read';
		if ($rights & 2) $user->grants[] = 'lmdbcrm.widgets.readall';
		if ($scenario === 'native') $user->grants = array_values(array_diff($user->grants, array('propal.lire')));
		if ($scenario === 'external') $user->socid = 42;
		if ($scenario === 'module') $enabledModules['lmdbcrm'] = false;
		$box = new lmdbcrm_graph_signedturnover_entities($db);
		$box->box_id = 400;
		$allowed = $rights !== 0 && $scenario === 'shared';
		check($box->hidden === !$allowed, 'entity catalogue '.$scenario);
		$cache = oldCache($box, 'SECRET_ENTITY_CACHE');
		$debug = captureLoad($box);
		$html = $box->showBox(null, null, 1);
		check($debug === '' && strpos($html, 'SECRET') === false, 'entity diagnostics/cache');
		if (!$allowed) {
			check(!$db->queries && $html === '', 'entity denial before SQL and render');
		} else {
			check(count($db->queries) === 1 && strpos($html, 'NoRecordFound') !== false, 'one current-year query and native empty state');
			$sql = $db->queries[0];
			check(strpos($sql, '2025-') === false && strpos($sql, '2024-') === false, 'no historical series');
			check(strpos($sql, 'p.entity IN (1,2)') !== false && strpos($sql, 'sc.fk_user = 7') !== false, 'shared and native commercial scopes');
			check((strpos($sql, 'p.fk_user_author = 7') !== false) === (($rights & 2) === 0), 'personal/full scopes');
			check(!file_exists($cache) && strpos($html, 'imgclose400') !== false, 'native identity and fresh cache');
		}
	}
}
resetContext();
$enabledModules['multicompany'] = true;
$entities['propal'] = implode(',', range(1, 12));
$user->grants[] = 'lmdbcrm.widgets.readall';
$db = new EntityGraphDb();
$allowTestGraphs = true;
$box = new lmdbcrm_graph_signedturnover_entities($db);
$box->box_id = 401;
captureLoad($box);
$html = $box->showBox(null, null, 1);
check(count($lastTestGraph->data) === 12 && count($lastTestGraph->data[0]) === 13, '12 months and one series per entity without truncation');
check(count($testGraphCalls['SetLegend'][0]) === 12 && $testGraphCalls['SetLegend'][0][0] === 'Entity &lt;1&gt;', 'escaped entity labels');
check(count(array_unique($lastTestGraph->datacolor)) === 12, 'distinct stable colours beyond three entities');
check($lastTestGraph->data[8][1] === 0.0 && $lastTestGraph->data[8][12] === 1200.0, 'zero and positive series kept separately');
check($lastTestGraph->data[0][12] === 0.0, 'missing months filled with zero');
$db->zero = true;
captureLoad($box);
check(strpos($box->showBox(null, null, 1), 'test-graph') !== false, 'signed zero amounts remain valid data');
$conf->global->SOCIETE_FISCAL_MONTH_START = 4;
captureLoad($box);
check(strpos(end($db->queries), '2026-04-01') !== false && strpos(end($db->queries), '2027-03-31') !== false, 'one common shifted fiscal year');
$entities['propal'] = '1,2';
check($box->showBox(null, null, 1) === '' && !$box->info_box_contents, 'sharing reduction between load and render refuses stale data');
$db->empty = true;
captureLoad($box);
$html = $box->showBox(null, null, 1);
check(strpos($html, 'NoRecordFound') !== false && strpos($html, 'test-graph') === false, 'populated to empty reload clears graph');
$db->failQuery = true;
captureLoad($box);
$html = $box->showBox(null, null, 1);
check(strpos($html, 'Error') !== false && strpos($html, 'NoRecordFound') === false && strpos($html, 'SELECT') === false, 'SQL failure distinct from empty period');
$db->failQuery = false;
$db->empty = false;
captureLoad($box);
$user->grants = array('propal.lire', 'lmdbcrm.widgets.read');
check($box->showBox(null, null, 1) === '', 'full to personal refuses old entity series');
captureLoad($box);
check(strpos(end($db->queries), 'p.fk_user_author = 7') !== false, 'personal reload uses own author');
$user->grants = array('propal.lire');
check($box->showBox(null, null, 1) === '', 'revocation refuses loaded graph');
$allowTestGraphs = false;
