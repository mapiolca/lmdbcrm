<?php
/* Copyright (C) 2001-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2025		Pierre Ardoin						<developpeur@lesmetiersdubatiment.fr>
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

// Manage sorting and search parameters
	$sortfield = GETPOST('sortfield', 'aZ09');
	$sortorder = GETPOST('sortorder', 'aZ09');
$validSortFields = array('total_count', 'signed_count', 'total_amount', 'signed_amount', 'conversion_rate', 'userid');

$search_date_start_input = trim(GETPOST('search_date_start', 'alphanohtml'));
$search_date_end_input = trim(GETPOST('search_date_end', 'alphanohtml'));

	$search_date_start = dol_stringtotime($search_date_start_input);
	$search_date_end = dol_stringtotime($search_date_end_input);

if (empty($search_date_start_input)) {
	$search_date_start = 0;
}
if (empty($search_date_end_input)) {
	$search_date_end = 0;
}

$search_user = trim(GETPOST('search_user', 'alphanohtml'));
$search_total_count = GETPOSTISSET('search_total_count') ? trim(GETPOST('search_total_count', 'alphanohtml')) : '';
$search_signed_count = GETPOSTISSET('search_signed_count') ? trim(GETPOST('search_signed_count', 'alphanohtml')) : '';
$search_total_amount = GETPOSTISSET('search_total_amount') ? price2num(GETPOST('search_total_amount', 'alpha'), 'MT') : '';
$search_signed_amount = GETPOSTISSET('search_signed_amount') ? price2num(GETPOST('search_signed_amount', 'alpha'), 'MT') : '';
$search_conversion_rate = GETPOSTISSET('search_conversion_rate') ? price2num(GETPOST('search_conversion_rate', 'alpha'), 'MT') : '';

if (empty($sortfield) || !in_array($sortfield, $validSortFields, true)) {
	$sortfield = 'signed_count';
}
if (empty($sortorder) || !in_array(dol_strtoupper($sortorder), array('ASC', 'DESC'), true)) {
	$sortorder = 'DESC';
}

// Prepare url parameters for listing
$param = '';
if (!empty($search_date_start_input)) {
	$param .= '&search_date_start='.urlencode($search_date_start_input);
}
if (!empty($search_date_end_input)) {
	$param .= '&search_date_end='.urlencode($search_date_end_input);
}
if (!empty($search_user)) {
	$param .= '&search_user='.urlencode($search_user);
}
if (dol_strlen((string) $search_total_count)) {
	$param .= '&search_total_count='.urlencode($search_total_count);
}
if (dol_strlen((string) $search_signed_count)) {
	$param .= '&search_signed_count='.urlencode($search_signed_count);
}
if ($search_total_amount !== '') {
	$param .= '&search_total_amount='.urlencode($search_total_amount);
}
if ($search_signed_amount !== '') {
	$param .= '&search_signed_amount='.urlencode($search_signed_amount);
}
if ($search_conversion_rate !== '') {
	$param .= '&search_conversion_rate='.urlencode($search_conversion_rate);
}

$form = new Form($db);
$userstatic = new User($db);

$title = $langs->trans('LmdbCrmSalesRepRanking');

	llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-lmdbcrm page-commercial-ranking');

// Title bar following Dolibarr list layout
	print load_fiche_titre($title, '', 'chart');
	print '<br>';

// Build SQL request for ranking
	$sql = "SELECT u.rowid as userid, u.lastname, u.firstname, u.login, u.photo, u.email";
	$sql .= ", COUNT(p.rowid) as total_count";
	$sql .= ", SUM(CASE WHEN p.fk_statut IN (2, 4) THEN 1 ELSE 0 END) as signed_count";
	$sql .= ", SUM(p.total_ht) as total_amount";
	$sql .= ", SUM(CASE WHEN p.fk_statut IN (2, 4) THEN p.total_ht ELSE 0 END) as signed_amount";
	$sql .= ", CASE WHEN COUNT(p.rowid) > 0 THEN (SUM(CASE WHEN p.fk_statut IN (2, 4) THEN 1 ELSE 0 END) / COUNT(p.rowid)) * 100 ELSE 0 END as conversion_rate";
	$sql .= " FROM ".$db->prefix()."user as u";
	$sql .= " JOIN ".$db->prefix()."propal as p ON p.fk_user_author = u.rowid";
	$sql .= " WHERE p.fk_statut IN (1, 2, 3, 4)";
	$sql .= " AND p.entity IN (".getEntity('propal').")";
if (!empty($search_user)) {
	$sql .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_user);
}
if (!empty($search_date_start)) {
	$sql .= " AND p.datep >= '".$db->idate($search_date_start)."'";
}
if (!empty($search_date_end)) {
	$sql .= " AND p.datep <= '".$db->idate($search_date_end)."'";
}
	$sql .= " GROUP BY u.rowid, u.lastname, u.firstname, u.login, u.photo, u.email";

$having = array();
if (dol_strlen((string) $search_total_count)) {
	$having[] = " COUNT(p.rowid) = ".((int) $search_total_count);
}
if (dol_strlen((string) $search_signed_count)) {
	$having[] = " SUM(CASE WHEN p.fk_statut IN (2, 4) THEN 1 ELSE 0 END) = ".((int) $search_signed_count);
}
if ($search_total_amount !== '') {
	$having[] = " SUM(p.total_ht) = " . price2num($search_total_amount, 'MT');
}
if ($search_signed_amount !== '') {
	$having[] = " SUM(CASE WHEN p.fk_statut IN (2, 4) THEN p.total_ht ELSE 0 END) = " . price2num($search_signed_amount, 'MT');
}
if ($search_conversion_rate !== '') {
	$having[] = " CASE WHEN COUNT(p.rowid) > 0 THEN (SUM(CASE WHEN p.fk_statut IN (2, 4) THEN 1 ELSE 0 END) / COUNT(p.rowid)) * 100 ELSE 0 END = " . price2num($search_conversion_rate, 'MT');
}

if (!empty($having)) {
	$sql .= " HAVING".implode(' AND', $having);
}
$sql .= $db->order($db->escape($sortfield), $db->escape($sortorder));

	$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit();
}

	$num = $db->num_rows($resql);

// Render list inspired by core proposal list layout
	print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" name="search_form">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">';

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre" colspan="6">';
	print '<div class="nowraponall">';
	print '<span class="opacitymedium">'.$langs->trans('PeriodRange').' : </span>';
	print '<input class="flat datepicker" type="text" name="search_date_start" value="'.($search_date_start ? dol_print_date($search_date_start, 'day') : '').'" autocomplete="off">';
	print ' - ';
	print '<input class="flat datepicker" type="text" name="search_date_end" value="'.($search_date_end ? dol_print_date($search_date_end, 'day') : '').'" autocomplete="off">';
	print '</div>';
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_user" value="'.dol_escape_htmltag($search_user).'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_total_count" value="'.(($search_total_count || $search_total_count === 0) ? dol_escape_htmltag($search_total_count) : '').'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_signed_count" value="'.(($search_signed_count || $search_signed_count === 0) ? dol_escape_htmltag($search_signed_count) : '').'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_total_amount" value="'.($search_total_amount !== '' ? dol_escape_htmltag($search_total_amount) : '').'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_signed_amount" value="'.($search_signed_amount !== '' ? dol_escape_htmltag($search_signed_amount) : '').'">';
	print '</td>';
	print '<td class="liste_titre center">';
	print '<input class="flat" type="text" name="search_conversion_rate" value="'.($search_conversion_rate !== '' ? dol_escape_htmltag($search_conversion_rate) : '').'">';
	print $form->showFilterButtons('right');
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('LmdbCrmSalesRep'), $_SERVER['PHP_SELF'], 'userid', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans('LmdbCrmProposalsCount'), $_SERVER['PHP_SELF'], 'total_count', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre($langs->trans('LmdbCrmSignedProposalsCount'), $_SERVER['PHP_SELF'], 'signed_count', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre($langs->trans('LmdbCrmQuotedAmount'), $_SERVER['PHP_SELF'], 'total_amount', '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre($langs->trans('LmdbCrmSignedAmount'), $_SERVER['PHP_SELF'], 'signed_amount', '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre($langs->trans('LmdbCrmConversionRate'), $_SERVER['PHP_SELF'], 'conversion_rate', '', $param, '', $sortfield, $sortorder, 'center ');
	print '</tr>';


if ($num > 0) {
	while ($obj = $db->fetch_object($resql)) {
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
		$conversionDisplay = price($obj->conversion_rate, 0, $langs, 0, 0, 2, '', 1, 0);
		print '<td class="center">'.$conversionDisplay.' %</td>';
		print '</tr>';
	}
} else {
	print '<tr class="oddeven"><td colspan="6" class="opacitymedium center">'.$langs->trans('LmdbCrmNoRankingData').'</td></tr>';
}

	print '</table>';
	print '</div>';
	print '</form>';

$db->free($resql);

llxFooter();

$db->close();
