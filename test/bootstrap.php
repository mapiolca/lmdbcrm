<?php
/** Test harness: real native renderers/descriptor, simulated session, SQL and helpers. */
error_reporting(E_ALL & ~E_DEPRECATED); // Older native descriptors use dynamic properties on PHP 8.2+.
set_error_handler(function ($level, $message, $file, $line) {
	if (error_reporting() & $level) {
		throw new ErrorException($message, 0, $level, $file, $line);
	}
	return false;
});
date_default_timezone_set('UTC');

$moduleRoot = dirname(__DIR__);
$coreSource = getenv('LMDBCRM_CORE_SOURCE');
if (!$coreSource || !is_file($coreSource.'/core/boxes/modules_boxes.php')) {
	throw new RuntimeException('Set LMDBCRM_CORE_SOURCE to an unmodified Dolibarr htdocs directory.');
}
$fixture = $moduleRoot.'/.test-cache/run-'.bin2hex(random_bytes(6));
foreach (array('core/boxes', 'core/modules', 'core/lib', 'core/class', 'comm/propal/class', 'user/class', 'commande/class', 'societe/class', 'data/users/temp/widgets') as $dir) {
	mkdir($fixture.'/'.$dir, 0777, true);
}
foreach (array('core/boxes/modules_boxes.php', 'core/modules/DolibarrModules.class.php') as $file) {
	copy($coreSource.'/'.$file, $fixture.'/'.$file);
}
foreach (array('core/lib/files.lib.php', 'core/lib/date.lib.php', 'core/class/infobox.class.php', 'core/class/dolgraph.class.php', 'core/class/html.form.class.php', 'comm/propal/class/propal.class.php', 'user/class/user.class.php', 'commande/class/commande.class.php', 'societe/class/societe.class.php') as $file) {
	file_put_contents($fixture.'/'.$file, '<?php // Dependency supplied by the test harness.');
}
file_put_contents($fixture.'/main.inc.php', '<?php return 1;');
define('DOL_DOCUMENT_ROOT', $fixture);
define('DOL_DATA_ROOT', $fixture.'/data');
define('DOL_URL_ROOT', '');
define('DOL_VERSION', getenv('LMDBCRM_CORE_VERSION') ?: '20.0.0');
define('MAIN_DB_PREFIX', 'test_');
if (!defined('LOG_ERR')) define('LOG_ERR', 3);
if (!defined('LOG_DEBUG')) define('LOG_DEBUG', 7);

class TestResult
{
	public $rows;
	public function __construct($rows = array()) { $this->rows = $rows; }
}
class DoliDB
{
	public $queries = array();
	public $failQuery = false;
	public function query($sql, $ignore = 0) {
		$this->queries[] = $sql;
		if ($this->failQuery) return false;
		if (strpos($sql, ' as value') !== false) return new TestResult(array((object) array('value' => 1)));
		if (strpos($sql, 'count(*) as nb') !== false) return new TestResult(array((object) array('nb' => 0)));
		return new TestResult();
	}
	public function fetch_object($result) { return array_shift($result->rows); }
	public function num_rows($result) { return count($result->rows); }
	public function free($result) {}
	public function close() {}
	public function prefix() { return MAIN_DB_PREFIX; }
	public function idate($date) { return gmdate('Y-m-d H:i:s', (int) $date); }
	public function escape($text) { return str_replace("'", "''", (string) $text); }
	public function sanitize($text) { return $text; }
	public function decrypt($text) { return $text; }
	public function plimit($limit, $offset = 0) { return ' LIMIT '.(int) $limit.' OFFSET '.(int) $offset; }
	public function order($field, $direction) { return ' ORDER BY '.$field.' '.$direction; }
	public function lasterror() { return 'SIMULATED_SQL_ERROR'; }
}
class User
{
	public $id = 7;
	public $socid = 0;
	public $admin = 0;
	public $grants = array();
	public function __construct($db = null) {}
	public function hasRight($module, $first, $second = '') { return in_array($module.'.'.$first.($second === '' ? '' : '.'.$second), $this->grants, true); }
	public function addrights(...$args) { throw new RuntimeException('Automatic grant attempted'); }
	public function clearrights() { throw new RuntimeException('Implicit administrator grant reload'); }
}
class Propal { const STATUS_SIGNED = 2; const STATUS_BILLED = 4; }
class Commande { const STATUS_CLOSED = 3; public function __construct($db) {} }
class Societe { public function __construct($db) {} }
class Form
{
	public function __construct($db) {}
	public function selectDate(...$args) { return '<input class="test-date">'; }
	public function select_dolusers(...$args) { return 'REAL_USER_SELECTOR'; }
	public function showFilterButtons(...$args) { return '<button>Filter</button>'; }
}
class Translate
{
	public $defaultlang = 'fr_FR';
	public $charset_output = 'UTF-8';
	public function load($file) {}
	public function loadLangs($files) {}
	public function trans($key, ...$args) { return $key; }
	public function transnoentities($key, ...$args) { return $key; }
	public function transnoentitiesnoconv($key, ...$args) { return $key; }
}
class DolGraph
{
	public function __construct() { throw new RuntimeException('Unexpected real chart in empty/preview fixture'); }
}
class AccessDenied extends RuntimeException {}
class InfoBox { public static function getListOfPagesForBoxes() { return array(0 => 'Home'); } }
function accessforbidden(...$args) { throw new AccessDenied('ACCESS_DENIED'); }
function isModEnabled($module) { return !empty($GLOBALS['enabledModules'][$module]); }
function getEntity($element) { return $GLOBALS['entities'][$element] ?? (string) $GLOBALS['conf']->entity; }
function getDolGlobalString($key, $default = '') { return (string) ($GLOBALS['conf']->global->$key ?? $default); }
function getDolGlobalInt($key, $default = 0) { return (int) ($GLOBALS['conf']->global->$key ?? $default); }
function GETPOST($key, $type = '') { return $_GET[$key] ?? ($type === 'array' ? array() : ''); }
function GETPOSTINT($key) { return (int) GETPOST($key); }
function dol_now() { return strtotime('2026-09-28 12:00:00'); }
function dol_mktime($h, $m, $s, $month, $day, $year) { return gmmktime((int) $h, (int) $m, (int) $s, (int) $month, (int) $day, (int) $year); }
function dol_get_last_day($year, $month, ...$args) { return gmmktime(23, 59, 59, $month + 1, 0, $year); }
function dol_time_plus_duree($date, $value, $unit) { return strtotime(($value >= 0 ? '+' : '').$value.($unit === 'm' ? ' months' : ' days'), $date); }
function dol_print_date($date, $format, ...$args) { return gmdate(str_replace(array('%Y', '%m', '%d', '%b'), array('Y', 'm', 'd', 'M'), $format), $date); }
function dol_escape_htmltag($text, ...$args) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function dolPrintHTMLForAttribute($text, ...$args) { return dol_escape_htmltag($text); }
function dol_strtoupper($text) { return strtoupper($text); }
function dol_trunc($text, $limit = 0) { return $limit ? substr($text, 0, $limit) : $text; }
function img_picto($label, $icon, $attrs = '') { return '<span '.$attrs.'>'.dol_escape_htmltag($label).'</span>'; }
function dol_syslog(...$args) {}
function dol_buildpath($path, $mode = 0) { return $path; }
function dol_cache_refresh($directory, $filename, $delay) { return !is_file($directory.$filename) || dol_now() - $delay > filemtime($directory.$filename); }
function dol_filecache($directory, $filename, $html) { file_put_contents($directory.$filename, json_encode($html)); touch($directory.$filename, dol_now()); }
function dol_readcachefile($directory, $filename) { return json_decode(file_get_contents($directory.$filename)); }
function dol_delete_file($file, ...$args) { return empty($GLOBALS['failCacheDelete']) ? unlink($file) : false; }
function llxHeader(...$args) { print '<html><body>'; }
function llxFooter(...$args) { print '</body></html>'; }
function load_fiche_titre($title, ...$args) { return '<h1>'.$title.'</h1>'; }
function print_liste_field_titre($title, ...$args) { print '<th>'.$title.'</th>'; }
function dol_print_error(...$args) { throw new RuntimeException('SQL error'); }
function natural_search($fields, $value) { return ''; }

function resetContext()
{
	global $conf, $langs, $user, $db, $enabledModules, $entities, $failCacheDelete;
	$conf = (object) array('entity' => 1, 'global' => (object) array('MAIN_ACTIVATE_FILECACHE' => 1), 'currency' => 'EUR', 'use_javascript_ajax' => 1, 'modules' => array());
	$langs = new Translate();
	$user = new User();
	$user->grants = array('propal.lire', 'commande.lire');
	$db = new DoliDB();
	$enabledModules = array('lmdbcrm' => true, 'propal' => true, 'commande' => true);
	$entities = array('propal' => '1', 'commande' => '1', 'user' => '1');
	$failCacheDelete = false;
	$_GET = array('debug_lmdbcrmsignedquotes' => 1, 'debug_lmdbcrmsignedturnover' => 1);
	$_SERVER['CONTEXT_DOCUMENT_ROOT'] = DOL_DOCUMENT_ROOT;
	$_SERVER['PHP_SELF'] = '/lmdbcrm/commercial_ranking.php';
}
resetContext();
