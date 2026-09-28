<?php
/** Extract native rights methods unchanged; supporting user state and DB are test fixtures. */
$nativeUserMethods = '';
$nativeTokens = token_get_all(file_get_contents($coreSource.'/user/class/user.class.php'));
foreach (array('loadRights', 'hasRight', 'addrights') as $methodName) {
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
// Execute unchanged native methods with a test DB adapter and explicit fixture properties.
eval('class EligibilityNativeUser { public $db; public $id; public $error; public $context = array(); public $admin = 0; public $rights; public $nb_rights = 0; public $_tab_loaded = array(); public $all_permissions_are_loaded = 0; '.$nativeUserMethods.' }');
