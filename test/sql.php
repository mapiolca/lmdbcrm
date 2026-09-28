<?php
/** Real MariaDB queries and native registration; no running Dolibarr instance required. */
require __DIR__.'/bootstrap.php';
require $moduleRoot.'/core/modules/modLmdbCrm.class.php';
foreach (glob($moduleRoot.'/core/boxes/lmdbcrm_*.php') as $file) require $file;
$dsn = getenv('LMDBCRM_TEST_DSN');
if (!$dsn) throw new RuntimeException('LMDBCRM_TEST_DSN must point to an empty, disposable test database.');
$pdo = new PDO($dsn, getenv('LMDBCRM_TEST_USER'), getenv('LMDBCRM_TEST_PASSWORD'), array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

// CREATE without IF NOT EXISTS deliberately refuses a reused/nonempty test schema.
foreach (array(
	'const (name varchar(255), value varchar(255), entity int)',
	'rights_def (id int, entity int, libelle varchar(255), module varchar(128), module_origin varchar(128), module_position varchar(64), type varchar(8), bydefault int, perms varchar(128), subperms varchar(128), enabled varchar(255), PRIMARY KEY(id, entity))',
	'boxes_def (rowid int AUTO_INCREMENT PRIMARY KEY, file varchar(255), entity int, note varchar(255))',
	'boxes (rowid int AUTO_INCREMENT PRIMARY KEY, box_id int, position int, box_order varchar(32), fk_user int, entity int)',
	'user_rights (fk_user int, fk_id int, entity int)',
	'usergroup_rights (fk_usergroup int, fk_id int, entity int)',
	'user (rowid int PRIMARY KEY, lastname varchar(64), firstname varchar(64), login varchar(64), photo varchar(64), email varchar(128), statut int, entity int)',
	'societe (rowid int PRIMARY KEY, nom varchar(64), name_alias varchar(64), code_client varchar(64), code_compta varchar(64), client int, logo varchar(64), email varchar(128), entity int)',
	'societe_commerciaux (fk_soc int, fk_user int)',
	'propal (rowid int PRIMARY KEY, fk_soc int, fk_user_author int, entity int, fk_statut int, date_signature datetime, datec datetime, datep datetime, total_ht double(24,8))',
	'propaldet (rowid int PRIMARY KEY, fk_propal int, total_ht double(24,8), buy_price_ht double(24,8), qty double(24,8))',
	'commande (rowid int PRIMARY KEY, fk_soc int, entity int, ref varchar(64), tms datetime, date_commande datetime, ref_client varchar(64), fk_statut int, facture int, total_ht double(24,8), total_tva double(24,8), total_ttc double(24,8))',
) as $definition) $pdo->exec('CREATE TABLE '.MAIN_DB_PREFIX.$definition);

$pdo->exec("INSERT INTO test_const VALUES ('MAIN_MODULE_LMDBCRM', '1', 1), ('MAIN_MODULE_LMDBCRM', '1', 2)");
$pdo->exec("INSERT INTO test_user VALUES (7,'Allowed','Alice','alice','','a@example.invalid',1,1),(8,'Other','Bob','bob','','b@example.invalid',1,1),(9,'Shared','Carol','carol','','c@example.invalid',1,2)");
$pdo->exec("INSERT INTO test_societe VALUES (10,'Allowed','','A','',1,'','',1),(20,'Forbidden','','B','',1,'','',1),(30,'Shared','','C','',1,'','',2)");
$pdo->exec('INSERT INTO test_societe_commerciaux VALUES (10,7),(20,8),(30,9)');
foreach (array(array(1,10,7,1,100), array(2,20,8,1,900), array(3,30,9,2,5000)) as $row) {
	list($id,$soc,$author,$entity,$amount) = $row;
	$pdo->exec("INSERT INTO test_propal VALUES ($id,$soc,$author,$entity,2,'2026-09-20','2026-09-20','2026-09-20',$amount)");
	$pdo->exec('INSERT INTO test_propaldet VALUES ('.$id.','.$id.','.$amount.','.($amount / 2).',1)');
	$pdo->exec("INSERT INTO test_commande VALUES ($id,$soc,$entity,'CO$id','2026-09-20','2026-09-20','',3,0,$amount,0,$amount)");
}
$checks = 0;
function sqlCheck($condition, $message) { global $checks; $checks++; if (!$condition) throw new RuntimeException($message); }

// Capture the module's actual SQL, then execute it against distinguishable real rows.
foreach (array('restricted', 'expanded', 'shared', 'shared-restricted') as $scope) {
	foreach (glob($moduleRoot.'/core/boxes/lmdbcrm_*.php') as $file) {
		resetContext();
		$user->grants[] = 'lmdbcrm.widgets.read';
		$expanded = in_array($scope, array('expanded', 'shared'), true);
		$shared = strpos($scope, 'shared') === 0;
		if ($expanded) $user->grants[] = 'societe.client.voir';
		if ($shared) { $conf->entity = 2; $entities = array('propal' => '1,2', 'commande' => '1,2', 'user' => '1,2'); }
		$class = basename($file, '.php');
		$box = new $class($db);
		ob_start(); try { $box->loadBox(); } finally { ob_end_clean(); }
		foreach ($db->queries as $sql) {
			$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
			// A personal series always stays scoped to the author, independently of global read.
			$personal = strpos($sql, 'p.fk_user_author = 7') !== false;
			$current = strpos($sql, '2026-') !== false || strpos($sql, 'date_signature') === false;
			$count = $current ? (($expanded && !$personal) ? ($shared ? 3 : 2) : 1) : 0;
			$amount = $current ? (($expanded && !$personal) ? ($shared ? 6000 : 1000) : 100) : 0;
			foreach (array('qty', 'nb', 'total', 'signed') as $metric) {
				if ($rows && array_key_exists($metric, $rows[0])) sqlCheck((int) array_sum(array_column($rows, $metric)) === $count, $class.' '.$scope.' '.$metric);
			}
			foreach (array('amount', 'turnover', 'total_ht') as $metric) {
				if ($rows && array_key_exists($metric, $rows[0])) sqlCheck((float) array_sum(array_column($rows, $metric)) === (float) $amount, $class.' '.$scope.' '.$metric);
			}
			if ($rows && array_key_exists('cost', $rows[0])) sqlCheck((float) array_sum(array_column($rows, 'cost')) === (float) ($amount / 2), $class.' '.$scope.' margin cost scope');
		}
	}
}

// Exercise the ranking query itself, including the restricted LEFT JOIN aggregation.
foreach (array('restricted' => array(1, 100), 'expanded' => array(2, 1000), 'shared' => array(3, 6000), 'shared-restricted' => array(1, 100)) as $scope => $expected) {
	$process = proc_open(array(PHP_BINARY, __DIR__.'/ranking.php', 'sql-'.$scope), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
	$output = stream_get_contents($pipes[1]);
	$error = stream_get_contents($pipes[2]);
	fclose($pipes[1]); fclose($pipes[2]);
	sqlCheck(proc_close($process) === 0 && $error === '', 'ranking SQL capture '.$error);
	sqlCheck(preg_match('/QUERIES=(.+)/', $output, $matches) === 1, 'ranking query present');
	$queries = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
	sqlCheck(count($queries) === 1, 'one ranking aggregation');
	$rows = $pdo->query($queries[0])->fetchAll(PDO::FETCH_ASSOC);
	sqlCheck((int) array_sum(array_column($rows, 'total_count')) === $expected[0], 'ranking '.$scope.' count');
	sqlCheck((float) array_sum(array_column($rows, 'signed_amount')) === (float) $expected[1], 'ranking '.$scope.' amount');
}

class RegistrationDb extends DoliDB
{
	public $pdo;
	public $depth = 0;
	public function __construct($pdo) { $this->pdo = $pdo; }
	public function query($sql, $ignore = 0) { $this->queries[] = $sql; $result = $this->pdo->query($sql); return new TestResult($result->columnCount() ? $result->fetchAll(PDO::FETCH_OBJ) : array()); }
	public function last_insert_id(...$args) { return $this->pdo->lastInsertId(); }
	public function begin() { if ($this->depth++ === 0) $this->pdo->beginTransaction(); return 1; }
	public function commit() { if (--$this->depth === 0) $this->pdo->commit(); return 1; }
	public function rollback() { if ($this->depth > 0) $this->pdo->rollBack(); $this->depth = 0; return 1; }
}
/** Only unrelated activation effects are simulated; native box/right registration executes SQL. */
class RegistrationModule extends modLmdbCrm
{
	public function __construct($db) { parent::__construct($db); $this->name = 'LmdbCrm'; $this->const_name = 'MAIN_MODULE_LMDBCRM'; }
	protected function _load_tables($reldir, $onlywithsuffix = '') { return 1; }
	protected function _active() { return 0; }
	protected function _unactive() { return 0; }
	public function insert_tabs() { return 0; }
	public function delete_tabs() { return 0; }
	public function insert_module_parts() { return 0; }
	public function delete_module_parts() { return 0; }
	public function insert_const() { return 0; }
	public function delete_const() { return 0; }
	public function insert_cronjobs() { return 0; }
	public function delete_cronjobs() { return 0; }
	public function insert_menus() { return 0; }
	public function delete_menus() { return 0; }
	public function create_dirs() { return 0; }
	public function delete_dirs() { return 0; }
}
resetContext();
$user->admin = 1;
$db = new RegistrationDb($pdo);
$module = new RegistrationModule($db);
sqlCheck($module->init() === 1, 'first activation');
sqlCheck((int) $pdo->query('SELECT COUNT(*) FROM test_rights_def')->fetchColumn() === 4, 'four native rights');
sqlCheck((int) $pdo->query('SELECT COUNT(*) FROM test_boxes')->fetchColumn() === 7, 'seven default boxes');
$pdo->exec("UPDATE test_boxes SET box_order='B4', fk_user=7 WHERE rowid=1");
$pdo->exec('INSERT INTO test_user_rights VALUES (7,45001102,1)');
$pdo->exec('INSERT INTO test_usergroup_rights VALUES (3,45001103,1)');
$before = $pdo->query('SELECT * FROM test_boxes ORDER BY rowid')->fetchAll(PDO::FETCH_ASSOC);
for ($i = 0; $i < 2; $i++) {
	sqlCheck($module->remove() === 1 && $module->init() === 1, 'disable/reactivate');
	sqlCheck($before === $pdo->query('SELECT * FROM test_boxes ORDER BY rowid')->fetchAll(PDO::FETCH_ASSOC), 'positions retained');
	sqlCheck((int) $pdo->query('SELECT COUNT(*) FROM test_rights_def')->fetchColumn() === 4, 'no duplicate rights');
	sqlCheck((int) $pdo->query('SELECT COUNT(*) FROM test_boxes_def')->fetchColumn() === 7, 'no duplicate definitions');
	sqlCheck((int) $pdo->query('SELECT COUNT(*) FROM test_user_rights')->fetchColumn() === 1, 'explicit user assignment retained');
	sqlCheck((int) $pdo->query('SELECT COUNT(*) FROM test_usergroup_rights')->fetchColumn() === 1, 'explicit group assignment retained');
}
$conf->entity = 2;
sqlCheck($module->init() === 1, 'second entity activation');
sqlCheck((int) $pdo->query('SELECT COUNT(*) FROM test_rights_def')->fetchColumn() === 8, 'rights registered by entity');
sqlCheck($before === $pdo->query('SELECT * FROM test_boxes WHERE entity=1 ORDER BY rowid')->fetchAll(PDO::FETCH_ASSOC), 'first entity unchanged');
print 'OK: '.$checks.' MariaDB assertions; native registration, isolated activation side effects.'.PHP_EOL;
