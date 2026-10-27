<?php
/** Consumer contract tests: actual CRM SQL/rendering, SQLite and a Lead API double.
 * The real Lead projection/visibility is tested in lmdblead/test/lead_conversion_statistics.php.
 */
require __DIR__.'/bootstrap.php';
require $moduleRoot.'/core/boxes/lmdbcrm_graph_conversionrates.php';
function dol_include_once($path) { return true; }
class LmdbLeadCompatibility
{
	public static $available = true;
	public static function isFeatureAvailable($code) { return self::$available; }
}
class LmdbLead_Lead
{
	private $db;
	public static $calls = array();
	public function __construct($db) { $this->db = $db; }
	public function conversionStatisticsSql(User $actor, ?int $from, ?int $to, ?array $ids): ?string
	{
		self::$calls[] = array($actor->id, $from, $to, $ids);
		$sql = 'SELECT COALESCE(t.fk_user_assign,0) AS userid, SUM(CASE WHEN t.status=7 THEN 1 ELSE 0 END) AS converted, SUM(CASE WHEN t.status=8 THEN 1 ELSE 0 END) AS lost, COUNT(*) AS total FROM test_lmdblead_lead AS t WHERE t.status IN (7,8) AND t.entity='.(int) $GLOBALS['conf']->entity;
		if ($from !== null) $sql .= " AND t.date_creation >= '".$this->db->idate($from)."'";
		if ($to !== null) $sql .= " AND t.date_creation <= '".$this->db->idate($to)."'";
		if ($ids !== null) $sql .= ' AND t.fk_user_assign IN ('.implode(',', $ids ?: array(-1)).')';
		return $sql.' GROUP BY COALESCE(t.fk_user_assign,0)';
	}
}
class ConversionDb extends DoliDB
{
	public $pdo;
	public $rankingRows = array();
	public $failLead = false;
	public function __construct() { $this->pdo = new PDO(getenv('LMDBCRM_CONVERSION_DSN') ?: 'sqlite::memory:', getenv('LMDBCRM_TEST_USER') ?: null, getenv('LMDBCRM_TEST_PASSWORD') ?: null, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)); }
	public function query($sql, $ignore = 0)
	{
		$this->queries[] = $sql;
		if ($this->failLead && strpos($sql, 'test_lmdblead_lead') !== false) return false;
		// Match MySQL decimal division in the legacy proposal expression.
		if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') $sql = str_replace(' / COUNT(p.rowid)', ' * 1.0 / COUNT(p.rowid)', $sql);
		$rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_OBJ);
		if (strpos($sql, 'as conversion_rate') !== false) $this->rankingRows = $rows;
		return new TestResult($rows);
	}
}
$checks = 0;
function verifyConversion($ok, $message) { $GLOBALS['checks']++; if (!$ok) throw new RuntimeException($message); }
function renderConversionRanking() {
	global $db, $user, $conf, $langs;
	ob_start(); try { include dirname(__DIR__).'/commercial_ranking.php'; return ob_get_contents(); } finally { ob_end_clean(); }
}
$db = new ConversionDb();
$db->pdo->exec('CREATE TABLE test_user(rowid INTEGER,statut INTEGER,fk_soc INTEGER,entity INTEGER,lastname TEXT,firstname TEXT,login TEXT,photo TEXT,email TEXT)');
foreach (array(7,8,9,10) as $id) $db->pdo->exec("INSERT INTO test_user VALUES($id,1,0,1,'USER-$id','','USER-$id','','')");
$db->pdo->exec('CREATE TABLE test_propal(rowid INTEGER,fk_user_author INTEGER,fk_statut INTEGER,total_ht NUMERIC,entity INTEGER,fk_soc INTEGER,datep TEXT,datec TEXT)');
$db->pdo->exec("INSERT INTO test_propal VALUES(1,7,2,100,1,10,'2026-09-01 00:00:00','2026-09-01 00:00:00'),(2,7,3,200,1,10,'2026-09-01 00:00:00','2026-09-01 00:00:00'),(3,8,2,500,1,10,'2026-09-01 00:00:00','2026-09-01 00:00:00')");
$db->pdo->exec('CREATE TABLE test_lmdblead_lead(rowid INTEGER,entity INTEGER,status INTEGER,fk_user_assign INTEGER,date_creation TEXT)');
$statuses = array(7,7,7,8,8,0,1,2,3,4,5,6,9,10,11,12);
foreach ($statuses as $i => $status) $db->pdo->exec("INSERT INTO test_lmdblead_lead VALUES($i,1,$status,7,'2026-09-01 00:00:00')");
$db->pdo->exec("INSERT INTO test_lmdblead_lead VALUES(20,1,8,8,'2026-09-30 23:59:59'),(21,1,7,NULL,'2026-09-15'),(22,2,7,7,'2026-09-15'),(23,1,7,7,'2026-10-01')");
$allowTestGraphs = true;
$user->grants = array('lmdbcrm.widgets.readall','lmdbcrm.ranking.readall','propal.lire','lmdblead.lead.read','lmdblead.lead.readall','societe.client.voir');
$enabledModules['lmdblead'] = true;
$_GET = array('lmdbcrmconv_fromyear'=>2026,'lmdbcrmconv_frommonth'=>9,'lmdbcrmconv_fromday'=>1,'lmdbcrmconv_toyear'=>2026,'lmdbcrmconv_tomonth'=>9,'lmdbcrmconv_today'=>30,
	'search_date_startyear'=>2026,'search_date_startmonth'=>9,'search_date_startday'=>1,'search_date_endyear'=>2026,'search_date_endmonth'=>9,'search_date_endday'=>30,'sortfield'=>'conversion_rate','sortorder'=>'DESC');
$fullGrants = $user->grants;
foreach (array(0,1,2,3) as $crmRights) {
	foreach (array(0,1,2,3) as $leadRights) {
		$user->admin = 1;
		$user->grants = array('propal.lire','societe.client.voir');
		if ($crmRights & 1) $user->grants[] = 'lmdbcrm.widgets.read';
		if ($crmRights & 2) $user->grants[] = 'lmdbcrm.widgets.readall';
		if ($leadRights & 1) $user->grants[] = 'lmdblead.lead.read';
		if ($leadRights & 2) $user->grants[] = 'lmdblead.lead.readall';
		LmdbLead_Lead::$calls = array(); $before = count($db->queries);
		$probe = new lmdbcrm_graph_conversionrates($db); $probe->box_id = 599; $probe->loadBox();
		$output = $probe->showBox(null,null,1);
		$expected = $crmRights && ($leadRights & 1) ? (($crmRights & 2) && ($leadRights & 2) ? 2 : 1) : 0;
		verifyConversion(count(LmdbLead_Lead::$calls) === $expected, 'Cross-module rights matrix controls query scopes without administrator bypass');
		if ($expected === 0) verifyConversion(count($db->queries) === $before && ($crmRights !== 0 || $output === ''), 'Denied metrics never query business data');
	}
}
$user->grants = $fullGrants; $user->socid = 42; LmdbLead_Lead::$calls = array();
$probe = new lmdbcrm_graph_conversionrates($db); $probe->loadBox();
verifyConversion($probe->showBox(null,null,1) === '' && LmdbLead_Lead::$calls === array(), 'External viewer denied before lead queries');
$user->socid = 0; LmdbLead_Lead::$calls = array();
$box = new lmdbcrm_graph_conversionrates($db); $box->box_id = 600;
$box->loadBox(); $html = $box->showBox(null,null,1);
verifyConversion(strpos($html,'60 %') !== false && strpos($html,'LmdbCrmConversionConverted') !== false, 'Personal lead rate is 60 percent with converted/lost labels');
verifyConversion(count(LmdbLead_Lead::$calls) === 2 && LmdbLead_Lead::$calls[0][3] === array(7) && LmdbLead_Lead::$calls[1][3] === null, 'Own and company scopes passed explicitly');
verifyConversion($lastTestGraph->data[0][1] === 4 && $lastTestGraph->data[1][1] === 3, 'Company counts include unassigned but exclude foreign and out-of-period leads');
$html = renderConversionRanking(); $rows = $db->rankingRows;
verifyConversion((float) $rows[0]->conversion_rate === 60.0 && (int) $rows[0]->userid === 7 && (float) $rows[1]->conversion_rate === 0.0 && $rows[2]->conversion_rate === null, 'SQL sorting keeps 60, zero and unavailable distinct');
verifyConversion((int) $rows[0]->total_count === 2 && (int) $rows[0]->signed_count === 1 && (float) $rows[0]->total_amount === 300.0 && (float) $rows[0]->signed_amount === 100.0, 'Multiple leads never multiply proposal counts/amounts');
$_GET['sortorder'] = 'ASC'; renderConversionRanking();
verifyConversion((int) $db->rankingRows[0]->userid === 8 && $db->rankingRows[2]->conversion_rate === null, 'Unavailable rates sort last in ascending order too');
$user->grants = array_values(array_diff($user->grants,array('lmdblead.lead.readall')));
verifyConversion($box->showBox(null,null,1) === '', 'Source full-read revocation invalidates loaded graph');
LmdbLead_Lead::$calls = array(); $box->loadBox(); $html = $box->showBox(null,null,1);
verifyConversion(count(LmdbLead_Lead::$calls) === 1 && strpos($html,'lmdbcrm-masked') !== false, 'No company query without source full read');
renderConversionRanking();
foreach ($db->rankingRows as $row) verifyConversion((int) $row->userid === 7 || $row->conversion_rate === null, 'Other salesperson rates unavailable without source readall');
$enabledModules['propal'] = false; $box->loadBox();
verifyConversion(!$box->hidden && strpos($box->showBox(null,null,1),'60 %') !== false, 'Lead widget works with proposals disabled');
$enabledModules['propal'] = true;
$conf->global->MAIN_NEED_EXPORT_PERMISSION_TO_READ_STATISTICS = 1;
LmdbLead_Lead::$calls = array(); $box->loadBox();
verifyConversion(LmdbLead_Lead::$calls === array() && strpos($box->showBox(null,null,1),'LmdbCrmConversionUnavailable') !== false, 'Missing export permission refuses all lead queries');
$conf->global->MAIN_NEED_EXPORT_PERMISSION_TO_READ_STATISTICS = 0;
$_GET['sortfield'] = 'userid'; $_GET['sortorder'] = 'ASC';
$db->failLead = true; $box->loadBox();
verifyConversion(strpos($box->showBox(null,null,1),'LmdbCrmConversionUnavailable') !== false, 'SQL failure is unavailable, never zero or empty');
$html = renderConversionRanking();
verifyConversion(strpos($html,'LmdbCrmConversionUnavailable') !== false && (float) $db->rankingRows[0]->total_amount === 300.0 && $db->rankingRows[0]->conversion_rate === null, 'Lead read failure preserves proposal metrics without a proposal rate fallback');
$db->failLead = false; LmdbLeadCompatibility::$available = false; LmdbLead_Lead::$calls = array(); $box->loadBox();
verifyConversion(LmdbLead_Lead::$calls === array() && strpos($box->showBox(null,null,1),'LmdbCrmConversionUnavailable') !== false, 'Unavailable source never queries or falls back');
LmdbLeadCompatibility::$available = true;
$user->grants = array_values(array_diff($user->grants,array('lmdblead.lead.read'))); LmdbLead_Lead::$calls = array(); $box->loadBox();
verifyConversion(LmdbLead_Lead::$calls === array() && strpos($box->showBox(null,null,1),'LmdbCrmConversionUnavailable') !== false, 'Missing lead read never queries the source');
$user->grants[] = 'lmdblead.lead.read';
$db->pdo->exec('UPDATE test_lmdblead_lead SET status=11 WHERE fk_user_assign=7'); $box->loadBox();
verifyConversion(strpos($box->showBox(null,null,1),'LmdbCrmConversionLeadsNoData') !== false, 'Zero denominator is no data');
$db->pdo->exec("UPDATE test_lmdblead_lead SET status=8 WHERE rowid=0"); $box->loadBox();
verifyConversion(strpos($box->showBox(null,null,1),'0 %') !== false, 'Lost-only cohort has a valid zero rate');
$enabledModules['lmdblead'] = false;
verifyConversion($box->showBox(null,null,1) === '', 'Source change invalidates loaded graph');
$box->loadBox(); $html = $box->showBox(null,null,1);
verifyConversion(strpos($html,'LmdbCrmConversionSigned') !== false && strpos(end($db->queries),'test_propal') !== false, 'Disabled Lead restores the original proposal source');
renderConversionRanking();
verifyConversion((int) $db->rankingRows[0]->total_count === 2 && (float) $db->rankingRows[0]->conversion_rate === 50.0, 'Proposal rate remains unchanged with Lead disabled');
echo "OK: $checks CRM conversion checks; real ".$db->pdo->getAttribute(PDO::ATTR_DRIVER_NAME)." aggregation/ranking and native renderer, simulated Lead interface.\n";
