<?php
/** Execute the native menu expression evaluator, not PHP eval of the expression itself. */
require __DIR__.'/bootstrap.php';
require $moduleRoot.'/core/modules/modLmdbCrm.class.php';
$source = file_get_contents($coreSource.'/core/lib/functions.lib.php');
$tokens = token_get_all($source);
// Load unmodified native functions without importing all of functions.lib.php over our doubles.
foreach (array('dol_eval', 'dol_eval_standard') as $name) {
	for ($i = 0; $i < count($tokens); $i++) {
		if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
		$j = $i + 1;
		while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
		if (!is_array($tokens[$j]) || $tokens[$j][1] !== $name) continue;
		$code = ''; $depth = 0; $started = false;
		for (; $i < count($tokens); $i++) {
			$token = $tokens[$i];
			$code .= is_array($token) ? $token[1] : $token;
			if ($token === '{') { $depth++; $started = true; }
			if ($token === '}') $depth--;
			if ($started && $depth === 0) break;
		}
		eval($code);
		break;
	}
}
resetContext();
$descriptor = new modLmdbCrm($db);
foreach (array(null, 0, '', '0') as $socid) {
	foreach (array(0, 1, 2, 3) as $rights) {
		foreach (array(false, true) as $native) {
			$user->socid = $socid;
			$user->admin = 1; // No local administrator override.
			$user->grants = $native ? array('propal.lire') : array();
			if ($rights & 1) $user->grants[] = 'lmdbcrm.ranking.read';
			if ($rights & 2) $user->grants[] = 'lmdbcrm.ranking.readall';
			$result = dol_eval($descriptor->menu[0]['perms'], 1, 1, '1');
			if ((int) $result !== (int) ($native && $rights !== 0)) {
				throw new RuntimeException('Native menu evaluation failed: '.var_export($result, true));
			}
		}
	}
}
if ($descriptor->menu[0]['user'] !== 0) throw new RuntimeException('Ranking menu must remain internal only');
print 'OK: 32 native dol_eval checks; internal menu type retained; Dolibarr '.DOL_VERSION.PHP_EOL;
