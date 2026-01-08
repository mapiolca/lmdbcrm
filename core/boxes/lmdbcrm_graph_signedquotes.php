<?php
/* Copyright (C) 2004-2017      Laurent Destailleur                     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024      Frédéric France                         <frederic.france@free.fr>
 * Copyright (C) 2025           Pierre Ardoin                           <developpeur@lesmetiersdubatiment.fr>
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
 * \file    lmdbcrm/core/boxes/lmdbcrm_graph_signedquotes.php
 * \ingroup lmdbcrm
 * \brief   Line graph widget for signed quotes count by month on current year (Company vs Me, N vs N-1), with date filters.
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

/**
 * Class to manage the signed quotes count graph box
 */
class lmdbcrm_graph_signedquotes extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = 'lmdbcrmsignedquotesgraph';

	/**
	 * @var string Box icon (in configuration page)
	 */
	public $boximg = 'chart';

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel = 'LmdbCrmSignedQuotesCurveTitle';

	/**
	 * @var string Box language file if it needs a specific language file.
	 */
	public $lang = 'lmdbcrm@lmdbcrm';

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('lmdbcrm', 'propal');

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $user;

		parent::__construct($db, $param);

		$this->db = $db;
		$this->param = $param;
		$this->hidden = empty($user->rights->propal->lire);
	}

	/**
	 * Load data into info_box_contents array. Called by Dolibarr before displaying the box.
	 *
	 * @param int<0,max> $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 1)
	{
		global $conf, $langs, $user;

		$langs->loadLangs(array('lmdbcrm@lmdbcrm', 'propal', 'main'));

		$debug = GETPOSTINT('debug_lmdbcrmsignedquotes');

		$now = dol_now();
		$yearN = (int) dol_print_date($now, '%Y');

		$yearStart = dol_mktime(0, 0, 0, 1, 1, $yearN);
		$yearEnd = dol_mktime(23, 59, 59, 12, 31, $yearN);

		// Use prefixes ending with "_" so selectDate() generates <prefix>day/month/year as <prefix>day => lmdbcrm_sq_datestart_day
		$prefixStart = 'lmdbcrm_sq_datestart_';
		$prefixEnd = 'lmdbcrm_sq_dateend_';

		$dateStartFilter = $this->getDateFromSelector($prefixStart);
		$dateEndFilter = $this->getDateFromSelector($prefixEnd);

		// Defaults
		if (empty($dateStartFilter)) $dateStartFilter = $yearStart;
		if (empty($dateEndFilter)) $dateEndFilter = $yearEnd;

		// Normalize to inclusive day range
		$dateStartFilter = dol_mktime(
			0, 0, 0,
			(int) dol_print_date($dateStartFilter, '%m'),
			(int) dol_print_date($dateStartFilter, '%d'),
			(int) dol_print_date($dateStartFilter, '%Y')
		);

		$dateEndFilter = dol_mktime(
			23, 59, 59,
			(int) dol_print_date($dateEndFilter, '%m'),
			(int) dol_print_date($dateEndFilter, '%d'),
			(int) dol_print_date($dateEndFilter, '%Y')
		);

		// Swap if inverted
		if ($dateStartFilter > $dateEndFilter) {
			$tmp = $dateStartFilter;
			$dateStartFilter = $dateEndFilter;
			$dateEndFilter = $tmp;
		}

		$fromN = $dateStartFilter;
		$toN = $dateEndFilter;

		$this->info_box_head = array(
			'text' => $langs->trans('LmdbCrmSignedQuotesCurveTitle'),
			'limit' => 0,
			'subpicto' => 'help',
			'subtext'  => dol_escape_htmltag($langs->transnoentitiesnoconv('LmdbCrmSignedQuotesCurveTooltip')),
			'subclass' => 'classfortooltip',
		);

		$contentHtml = '';

		// Filter form
		$form = new Form($this->db);
		$contentHtml .= '<div class="center" style="margin-bottom: 6px;">';
		$contentHtml .= '<form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" class="inline-block">';
		$contentHtml .= '<input type="hidden" name="mainmenu" value="'.dol_escape_htmltag(GETPOST('mainmenu', 'aZ09')).'">';
		$contentHtml .= '<input type="hidden" name="leftmenu" value="'.dol_escape_htmltag(GETPOST('leftmenu', 'aZ09')).'">';

		$contentHtml .= '<span class="nowrapfordate">';
		$contentHtml .= $form->selectDate($fromN, $prefixStart, 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		$contentHtml .= '</span> ';

		$contentHtml .= '<span class="nowrapfordate">';
		$contentHtml .= $form->selectDate($toN, $prefixEnd, 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		$contentHtml .= '</span> ';

		$contentHtml .= '<input class="button smallpaddingimp" type="submit" value="'.$langs->trans('Refresh').'">';
		$contentHtml .= '</form>';
		$contentHtml .= '</div>';

		// Same period shifted by -1 year for N-1
		$fromN1 = dol_time_plus_duree($fromN, -1, 'y');
		$toN1 = dol_time_plus_duree($toN, -1, 'y');

		// Fetch data (4 curves)
		$companyN = $this->fetchSignedQuotesCountByMonth($fromN, $toN, 0);
		$companyN1 = $this->fetchSignedQuotesCountByMonth($fromN1, $toN1, 0);
		$meN = $this->fetchSignedQuotesCountByMonth($fromN, $toN, (int) $user->id);
		$meN1 = $this->fetchSignedQuotesCountByMonth($fromN1, $toN1, (int) $user->id);

		$months = $this->buildMonthSequenceByRange($fromN, $toN);

		$graphData = array();
		$totalCount = 0;

		foreach ($months as $monthInfo) {
			$keyN = $monthInfo['key']; // YYYY-MM (year N)
			$keyN1 = sprintf('%04d-%02d', (int) $monthInfo['year'] - 1, (int) $monthInfo['month']); // same month, year N-1

			$vCompanyN = isset($companyN[$keyN]) ? (int) $companyN[$keyN] : 0;
			$vCompanyN1 = isset($companyN1[$keyN1]) ? (int) $companyN1[$keyN1] : 0;
			$vMeN = isset($meN[$keyN]) ? (int) $meN[$keyN] : 0;
			$vMeN1 = isset($meN1[$keyN1]) ? (int) $meN1[$keyN1] : 0;

			$totalCount += ($vCompanyN + $vCompanyN1 + $vMeN + $vMeN1);

			$graphData[] = array(
				$monthInfo['label'],
				$vCompanyN,
				$vCompanyN1,
				$vMeN,
				$vMeN1
			);
		}

		if ($debug) {
			var_dump(array(
				'debug_scope' => 'lmdbcrm_graph_signedquotes::loadBox',
				'yearN' => $yearN,
				'dateStartFilter' => $dateStartFilter,
				'dateEndFilter' => $dateEndFilter,
				'fromN' => $fromN,
				'toN' => $toN,
				'fromN1' => $fromN1,
				'toN1' => $toN1,
				'companyN' => $companyN,
				'companyN1' => $companyN1,
				'meN' => $meN,
				'meN1' => $meN1,
				'months' => $months,
				'graphData' => $graphData,
				'totalCount' => $totalCount,
			));
		}

		if ($totalCount <= 0) {
			$contentHtml .= '<div class="center opacitymedium">'.$langs->trans('LmdbCrmSignedQuotesCurveNoData').'</div>';
		} else {
			$graph = new DolGraph();
			$graph->SetData($graphData);

			$rangeLabelCurrent = $this->buildYearRangeLabel($fromN, $toN);
			$rangeLabelPrev = $this->buildYearRangeLabel($fromN1, $toN1);

			$graph->SetLegend(array(
				$langs->trans('LmdbCrmSignedQuotesLegendCompany', $rangeLabelCurrent),
				$langs->trans('LmdbCrmSignedQuotesLegendCompany', $rangeLabelPrev),
				$langs->trans('LmdbCrmSignedQuotesLegendMe', $rangeLabelCurrent),
				$langs->trans('LmdbCrmSignedQuotesLegendMe', $rangeLabelPrev),
			));

			$graph->SetDataColor(array('#2e78c2', '#a3a3a3', '#2da44e', '#d8a200'));
			$graph->SetType(array('lines'));
			$graph->setHeight('320');
			$graph->setWidth('740');
			$graph->setShowLegend(1);
			$graph->setMinValue(0);

			$graphId = 'lmdbcrmsignedquotescy_e'.((int) $conf->entity).'_'.substr(md5($fromN.'_'.$toN), 0, 8);
			$graph->draw($graphId);

			$contentHtml .= '<div class="center">'.$graph->show(0).'</div>';
		}

		$this->info_box_contents = array();
		$this->info_box_contents[] = array(
			0 => array(
				'td' => 'class="center"',
				'asis' => 1,
				'align' => 'center',
				'css' => 'nohover center',
				'url' => '',
				'text' => $contentHtml,
			),
		);
	}

	/**
	 * Method to show box.
	 *
	 * @param ?array $head Box header
	 * @param ?array $contents Box contents
	 * @param int<0,1> $nooutput No print, only return string
	 * @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

	/**
	 * Read a date from a selectDate() selector prefix (expects <prefix>day/month/year).
	 *
	 * @param string $prefix
	 * @return int Timestamp or 0 if not provided
	 */
	protected function getDateFromSelector($prefix)
	{
		$day = GETPOSTINT($prefix.'day');
		$month = GETPOSTINT($prefix.'month');
		$year = GETPOSTINT($prefix.'year');

		if (!empty($year) && !empty($month) && !empty($day)) {
			return dol_mktime(0, 0, 0, $month, $day, $year);
		}

		return 0;
	}

	/**
	 * EN: Build month keys and labels for any date range (inclusive).
	 * FR: Construire les clés et libellés mensuels pour une période quelconque (inclusive).
	 *
	 * @param int $fromDate
	 * @param int $toDate
	 * @return array
	 */
	protected function buildMonthSequenceByRange($fromDate, $toDate)
	{
		$months = array();

		$includeYear = ((int) dol_print_date($fromDate, '%Y') !== (int) dol_print_date($toDate, '%Y'));
		$ts = dol_mktime(12, 0, 0, (int) dol_print_date($fromDate, '%m'), 1, (int) dol_print_date($fromDate, '%Y'));
		$tsEnd = dol_mktime(12, 0, 0, (int) dol_print_date($toDate, '%m'), 1, (int) dol_print_date($toDate, '%Y'));

		while ($ts <= $tsEnd) {
			$year = (int) dol_print_date($ts, '%Y');
			$month = (int) dol_print_date($ts, '%m');

			$months[] = array(
				'key' => dol_print_date($ts, '%Y-%m'),
				'label' => dol_print_date($ts, $includeYear ? '%b %Y' : '%b'),
				'month' => $month,
				'year' => $year,
			);
			$ts = dol_time_plus_duree($ts, 1, 'm');
		}

		return $months;
	}

	/**
	 * Fetch signed quotes count grouped by month.
	 *
	 * Uses p.date_signature and signed/billed statuses (same base as signed turnover).
	 * User scope = author (creator) of the proposal: p.fk_user_author
	 *
	 * @param int $fromDate
	 * @param int $toDate
	 * @param int $userId 0 = all company, else filter
	 * @return array
	 */
	protected function fetchSignedQuotesCountByMonth($fromDate, $toDate, $userId = 0)
	{
		$data = array();

		$signedStatus = (defined('Propal::STATUS_SIGNED') ? Propal::STATUS_SIGNED : 2);
		$billedStatus = (defined('Propal::STATUS_BILLED') ? Propal::STATUS_BILLED : 4);
		$debug = GETPOSTINT('debug_lmdbcrmsignedquotes');

		$sql = "SELECT YEAR(p.date_signature) as y, MONTH(p.date_signature) as m, COUNT(p.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		$sql .= " WHERE p.entity IN (".getEntity('propal').")";
		$sql .= " AND p.fk_statut IN (".((int) $signedStatus).",".((int) $billedStatus).")";
		$sql .= " AND p.date_signature IS NOT NULL";
		$sql .= " AND p.date_signature >= '".$this->db->idate($fromDate)."'";
		$sql .= " AND p.date_signature <= '".$this->db->idate($toDate)."'";
		if (!empty($userId)) {
			$sql .= " AND p.fk_user_author = ".((int) $userId);
		}
		$sql .= " GROUP BY YEAR(p.date_signature), MONTH(p.date_signature)";

		if ($debug) {
			var_dump(array(
				'debug_scope' => 'lmdbcrm_graph_signedquotes::fetchSignedQuotesCountByMonth',
				'sql' => $sql,
				'fromDate' => $fromDate,
				'toDate' => $toDate,
				'userId' => $userId,
				'signedStatus' => $signedStatus,
				'billedStatus' => $billedStatus,
			));
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$key = sprintf('%04d-%02d', (int) $obj->y, (int) $obj->m);
				$data[$key] = (int) $obj->nb;
			}
			$this->db->free($resql);
		}

		if ($debug) {
			var_dump(array(
				'debug_scope' => 'lmdbcrm_graph_signedquotes::fetchSignedQuotesCountByMonth_results',
				'results' => $data,
			));
		}

		return $data;
	}

	/**
	 * EN: Build a year or year range label for legends.
	 * FR: Construire un libellé d'année ou de plage d'années pour les légendes.
	 *
	 * @param int $fromDate
	 * @param int $toDate
	 * @return string
	 */
	protected function buildYearRangeLabel($fromDate, $toDate)
	{
		$fromYear = (int) dol_print_date($fromDate, '%Y');
		$toYear = (int) dol_print_date($toDate, '%Y');

		if ($fromYear === $toYear) {
			return (string) $fromYear;
		}

		return $fromYear.'-'.$toYear;
	}
}
