<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025           Pierre Ardoin                           <erp@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lmdbcrm/admin/setup.php
 * \ingroup lmdbcrm
 * \brief   LmdbCrm setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once __DIR__.'/../lib/lmdbcrm.lib.php';
require_once __DIR__.'/../class/lmdbcrmconversion.class.php';
require_once __DIR__.'/../core/modules/modLmdbCrm.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array('admin', 'other', 'lmdbcrm@lmdbcrm'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// All three routes share the same administrator guard and native navigation.
$lmdbcrmAdminTab = isset($lmdbcrmAdminTab) && in_array($lmdbcrmAdminTab, array('compatibility', 'about'), true) ? $lmdbcrmAdminTab : 'settings';
$titles = array('settings' => 'LmdbCrmSetup', 'compatibility' => 'LmdbCrmCompatibility', 'about' => 'LmdbCrmAbout');
$title = $langs->trans($titles[$lmdbcrmAdminTab]);
$descriptor = new modLmdbCrm($db);
$phpmin = implode('.', $descriptor->phpmin);
$dolmin = implode('.', $descriptor->need_dolibarr_version);
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword=lmdbcrm">'.$langs->trans('BackToModuleList').'</a>';

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-lmdbcrm page-admin');
print load_fiche_titre($title, $linkback, $lmdbcrmAdminTab === 'about' ? 'info' : 'title_setup');
$head = lmdbcrmAdminPrepareHead();
print dol_get_fiche_head($head, $lmdbcrmAdminTab, $title, -1, $descriptor->picto);

if ($lmdbcrmAdminTab === 'settings') {
	$source = LmdbCrmConversion::source();
	print '<div class="underbanner opacitymedium">'.dol_escape_htmltag($langs->trans('LmdbCrmNoSetupNeeded')).'</div><br>';
	print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('LmdbCrmConversionRate').'</th></tr>';
	$rows = array(
		'LmdbCrmConversionSource' => $langs->trans($source['label']),
		'Status' => $langs->trans($source['available'] ? 'Available' : 'NotAvailable'),
		'LmdbCrmSourceSelection' => $langs->trans('LmdbCrmSourceSelectionHelp'),
		'LmdbCrmCalculation' => $langs->trans('LmdbCrmCalculationHelp'),
		'LmdbCrmLeadScope' => $langs->trans('LmdbCrmLeadScopeHelp'),
		'LmdbCrmOtherIndicators' => $langs->trans('LmdbCrmOtherIndicatorsHelp'),
	);
	if ($source['reason'] !== '') {
		$rows['Reason'] = $langs->trans($source['reason']);
	}
	foreach ($rows as $label => $value) {
		print '<tr class="oddeven"><td class="titlefield">'.$langs->trans($label).'</td><td>'.dol_escape_htmltag($value).'</td></tr>';
	}
	print '</table></div><br>';
	print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
	print '<tr class="liste_titre"><th>'.$langs->trans('LmdbCrmAccessAndWidgets').'</th><th>'.$langs->trans('Description').'</th></tr>';
	$rows = array(
		'LmdbCrmWidgetsSettings' => 'LmdbCrmWidgetsSettingsHelp',
		'Rights' => 'LmdbCrmPermissionsHelp',
		'LmdbCrmLeadAccess' => 'LmdbCrmLeadAccessHelp',
	);
	foreach ($rows as $label => $help) {
		print '<tr class="oddeven"><td class="titlefield">'.$langs->trans($label).'</td><td>'.dol_escape_htmltag($langs->trans($help)).'</td></tr>';
	}
	print '</table></div>';
} elseif ($lmdbcrmAdminTab === 'compatibility') {
	$source = LmdbCrmConversion::source();
	print '<div class="underbanner opacitymedium">'.dol_escape_htmltag($langs->trans('LmdbCrmCompatibilityHelp')).'</div><br>';
	print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('LmdbCrmEnvironment').'</th></tr>';
	$rows = array(
		'LmdbCrmDetectedDolibarr' => DOL_VERSION,
		'LmdbCrmDetectedPHP' => PHP_VERSION,
		'LmdbCrmMinimumDolibarr' => $dolmin,
		'LmdbCrmMinimumPHP' => $phpmin,
		'LmdbCrmTargetVersions' => '20, 21, 22, 23, 24, 25',
	);
	foreach ($rows as $label => $value) {
		print '<tr class="oddeven"><td class="titlefield">'.$langs->trans($label).'</td><td>'.dol_escape_htmltag($value).'</td></tr>';
	}
	print '</table></div><br>';
	print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	foreach (array('Code', 'Label', 'Description', 'LmdbCrmAvailableFrom', 'Status', 'Reason') as $label) {
		print '<th>'.$langs->trans($label).'</th>';
	}
	print '</tr><tr class="oddeven">';
	// The runtime source resolver is also the authority for this diagnostic.
	$values = array('conversion_statistics', $langs->trans('LmdbCrmConversionRate'),
		$langs->trans($source['label']), 'Dolibarr '.$dolmin.' / PHP '.$phpmin,
		$langs->trans($source['available'] ? 'Available' : 'NotAvailable'),
		$langs->trans($source['reason'] !== '' ? $source['reason'] : 'OK'));
	foreach ($values as $value) {
		print '<td>'.dol_escape_htmltag($value).'</td>';
	}
	print '</tr></table></div><br>';
	print '<div class="opacitymedium">'.dol_escape_htmltag($langs->trans('LmdbCrmConversionIntegrationHelp')).'</div><br>';
	print '<div class="opacitymedium">'.dol_escape_htmltag($langs->trans('LmdbCrmValidationLimits')).'</div>';
} else {
	print '<div class="underbanner opacitymedium">'.dol_escape_htmltag($langs->trans('LmdbCrmAboutHelp')).'</div><br>';
	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('LmdbCrmAboutGeneral').'</th></tr>';
	$rows = array(
		'Module' => $descriptor->name,
		'Version' => $descriptor->version,
		'Family' => $descriptor->family,
		'Description' => $langs->trans($descriptor->description),
		'LmdbCrmPublisher' => $descriptor->editor_name,
		'Author' => $descriptor->module_author,
		'DolibarrLicense' => $descriptor->module_license,
		'LmdbCrmMinimumDolibarr' => $dolmin,
		'LmdbCrmMinimumPHP' => $phpmin,
		'DependsOn' => $descriptor->depends ? implode(', ', $descriptor->depends) : $langs->trans('None'),
	);
	foreach ($rows as $label => $value) {
		print '<tr class="oddeven"><td class="titlefield">'.$langs->trans($label).'</td><td>'.dol_escape_htmltag($value).'</td></tr>';
	}
	print '</table></div></div><div class="fichehalfright">';
	print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('LmdbCrmAboutResources').'</th></tr>';
	$links = array(
		'LmdbCrmDocumentation' => array(dol_buildpath('/lmdbcrm/README.md', 1), 'README.md'),
		'LmdbCrmChangeLog' => array(dol_buildpath('/lmdbcrm/ChangeLog.md', 1), 'ChangeLog.md'),
		'LmdbCrmRepository' => array($descriptor->module_repository, $descriptor->module_repository),
		'LmdbCrmPublisher' => array('https://'.$descriptor->editor_url, $descriptor->editor_name),
	);
	foreach ($links as $label => $link) {
		print '<tr class="oddeven"><td class="titlefield">'.$langs->trans($label).'</td><td><a href="'.dol_escape_htmltag($link[0]).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag($link[1]).'</a></td></tr>';
	}
	print '</table></div><br>';
	print '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
	print '<tr class="liste_titre"><th>'.$langs->trans('LmdbCrmAboutMainFeatures').'</th></tr>';
	print '<tr class="oddeven"><td>'.dol_escape_htmltag($langs->trans('LmdbCrmAboutFeatures')).'</td></tr>';
	print '</table></div></div></div><div class="clearboth"></div>';
}

print dol_get_fiche_end();

llxFooter();
$db->close();
