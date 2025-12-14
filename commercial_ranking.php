<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
* Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
* Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
* Copyright (C) 2025           Pierre Ardoin                           <developpeur@lesmetiersdubatiment.fr>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

/**
* \file       lmdbcrm/commercial_ranking.php
* \ingroup    lmdbcrm
* \brief      Ranking list for sales representatives based on proposals
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
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

/**
* The main.inc.php has been included so the following variables are now defined:
* @var Conf $conf
* @var DoliDB $db
* @var Translate $langs
* @var User $user
*/
dol_include_once('/core/lib/date.lib.php');
dol_include_once('/user/class/user.class.php');

// Load translation files required by the page
$langs->loadLangs(array('lmdbcrm@lmdbcrm', 'commercial', 'propal'));

// Security check - Protection if external user
if (!empty($user->socid)) {
	accessforbidden();
}
if (!isModEnabled('propal')) {
	accessforbidden();
}
if (empty($user->rights->propal->lire)) {
	accessforbidden();
}

$sortfield = GETPOST('sortfield', 'aZ09');
$sortorder = GETPOST('sortorder', 'aZ09');
$validSortFields = array('total_count', 'signed_count', 'total_amount', 'signed_amount', 'userid');

$search_date_start = dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'));
$search_date_end = dol_mktime(23, 59, 59, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'));

if (empty($sortfield) || !in_array($sortfield, $validSortFields, true)) {
	$sortfield = 'signed_count';
}
if (empty($sortorder) || !in_array(dol_strtoupper($sortorder), array('ASC', 'DESC'), true)) {
	$sortorder = 'DESC';
}

$dateStartForSelect = $search_date_start ?: -1;
$dateEndForSelect = $search_date_end ?: -1;

$form = new Form($db);
$userstatic = new User($db);

llxHeader('', $langs->trans('LmdbCrmSalesRepRanking'), '', '', 0, 0, '', '', '', 'mod-lmdbcrm page-commercial-ranking');

print load_fiche_titre($langs->trans('LmdbCrmSalesRepRanking'), '', 'chart');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" class="liste_titre">';
print '<div class="fichehalfleft">';
print '<div class="nowraponall">';
print $langs->trans('DateStart').' ';
print $form->selectDate($dateStartForSelect, 'search_date_start', 0, 0, 1, '', 1, 1);
print '</div>';
print '</div>';
print '<div class="fichehalfright">';
print '<div class="nowraponall">';
print $langs->trans('DateEnd').' ';
print $form->selectDate($dateEndForSelect, 'search_date_end', 0, 0, 1, '', 1, 1);
print '</div>';
print '</div>';
print '<div class="clearboth"></div>';
print '<div class="center">';
print '<input type="submit" class="button" name="button_search" value="'.$langs->trans('Search').'">';
print ' <a class="button" href="'.$_SERVER['PHP_SELF'].'">'.$langs->trans('RemoveFilter').'</a>';
print '</div>';
print '</form>';

$sql = "SELECT u.rowid as userid, u.lastname, u.firstname, u.login, u.photo, u.email";
$sql .= ", COUNT(p.rowid) as total_count";
$sql .= ", SUM(CASE WHEN p.fk_statut IN (2, 4) THEN 1 ELSE 0 END) as signed_count";
$sql .= ", SUM(p.total_ht) as total_amount";
$sql .= ", SUM(CASE WHEN p.fk_statut IN (2, 4) THEN p.total_ht ELSE 0 END) as signed_amount";
$sql .= " FROM ".$db->prefix()."user as u";
$sql .= " JOIN ".$db->prefix()."propal as p ON p.fk_user_author = u.rowid";
$sql .= " WHERE p.fk_statut IN (1, 2, 3, 4)";
$sql .= " AND p.entity IN (".getEntity('propal').")";
if (!empty($search_date_start)) {
	$sql .= " AND p.datep >= '".$db->idate($search_date_start)."'";
}
if (!empty($search_date_end)) {
	$sql .= " AND p.datep <= '".$db->idate($search_date_end)."'";
}
$sql .= " GROUP BY u.rowid, u.lastname, u.firstname, u.login, u.photo, u.email";
$sql .= $db->order($db->escape($sortfield), $db->escape($sortorder));

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit();
}

$num = $db->num_rows($resql);

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans('LmdbCrmSalesRep').'</th>';
print '<th class="center">'.print_liste_field_titre($langs->trans('LmdbCrmProposalsCount'), $_SERVER['PHP_SELF'], 'total_count', '', '', '', $sortfield, $sortorder).'</th>';
print '<th class="center">'.print_liste_field_titre($langs->trans('LmdbCrmSignedProposalsCount'), $_SERVER['PHP_SELF'], 'signed_count', '', '', '', $sortfield, $sortorder).'</th>';
print '<th class="right">'.print_liste_field_titre($langs->trans('LmdbCrmQuotedAmount'), $_SERVER['PHP_SELF'], 'total_amount', '', '', '', $sortfield, $sortorder).'</th>';
print '<th class="right">'.print_liste_field_titre($langs->trans('LmdbCrmSignedAmount'), $_SERVER['PHP_SELF'], 'signed_amount', '', '', '', $sortfield, $sortorder).'</th>';
print '<th class="center">'.$langs->trans('LmdbCrmConversionRate').'</th>';
print '</tr>';

if ($num > 0) {
	while ($obj = $db->fetch_object($resql)) {
		$conversionRate = 0;
		if (!empty($obj->total_count)) {
			$conversionRate = ($obj->signed_count / $obj->total_count) * 100;
		}

		$userstatic->id = $obj->userid;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		$userstatic->login = $obj->login;
		$userstatic->email = $obj->email;

		print '<tr class="oddeven">';
		print '<td class="nowraponall">'.$userstatic->getNomUrl(-1, '', 0, 0, 0).'</td>';
		print '<td class="center">'.(int) $obj->total_count.'</td>';
		print '<td class="center">'.(int) $obj->signed_count.'</td>';
		print '<td class="right">'.price($obj->total_amount, 0, $langs, 0, 0, -1, $conf->currency).'</td>';
		print '<td class="right">'.price($obj->signed_amount, 0, $langs, 0, 0, -1, $conf->currency).'</td>';
		print '<td class="center">'.dol_print_decimal($conversionRate, 2).' %</td>';
		print '</tr>';
	}
} else {
	print '<tr class="oddeven"><td colspan="6" class="opacitymedium center">'.$langs->trans('LmdbCrmNoRankingData').'</td></tr>';
}

print '</table>';
print '</div>';

$db->free($resql);

llxFooter();

$db->close();
