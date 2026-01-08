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
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
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
		$todayEnd = dol_mktime(23, 59, 59, (int) dol_print_date($now, '%m'), (int) dol_print_date($now, '%d'), $yearN);

		// Prefixes for date selectors
		$prefixStart = 'lmdbcrm_sq_datestart';
		$prefixEnd = 'lmdbcrm_sq_dateend';

		// Detect if end date has been provided explicitly (avoid overriding user choice)
		$endProvided = $this->isDateProvidedInRequest($prefixEnd);

		// Read filters (support both naming styles produced by selectDate)
		$dateStartFilter = $this->getDateFromRequest($prefixStart, 0);
		$dateEndFilter = $this->getDateFromRequest($prefixEnd, 0);

		// Defaults
		if (empty($dateStartFilter)) $dateStartFilter = $yearStart;

		// Rule requested:
		// If start is 01/01 of current year, and end is not explicitly provided, then end must be 31/12 of same year.
		if (empty($dateEndFilter)) {
			$isStartJan1 = ((int) dol_print_date($dateStartFilter, '%Y') === $yearN
				&& (int) dol_print_date($dateStartFilter, '%m') === 1
				&& (int) dol_print_date($dateStartFilter, '%d') === 1);

			if ($isStartJan1 && !$endProvided) {
				$dateEndFilter = $yearEnd;
			} else {
				$dateEndFilter = $todayEnd;
			}
		}

		// Normalize to be inclusive (start at 00:00:00, end at 23:59:59)
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

		// Clamp filter to current year (X axis requirement: current year)
		$fromN = max($dateStartFilter, $yearStart);
		$toN = min($dateEndFilter, $yearEnd);

		// Header tooltip (same style as your signed turnover box)
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

		// If selected range is outside current year
		if ($fromN > $toN) {
			$contentHtml .= '<div class="center opacitymedium">'.$langs->trans('LmdbCrmSignedQuotesCurveNoData').'</div>';

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
			return;
		}

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
			$keyN1 = sprintf('%04d-%02d', $yearN - 1, (int) $monthInfo['month']); // same month, year N-1

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
				'prefixStart' => $prefixStart,
				'prefixEnd' => $prefixEnd,
				'endProvided' => $endProvided,
				'dateStartFilter_raw_normalized' => $dateStartFilter,
				'dateEndFilter_raw_normalized' => $dateEndFilter,
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

			$legend = array(
				$langs->trans('LmdbCrmSignedQuotesLegendCompany', $yearN),
				$langs->trans('LmdbCrmSignedQuotesLegendCompany', $yearN - 1),
				$langs->trans('LmdbCrmSignedQuotesLegendMe', $yearN),
				$langs->trans('LmdbCrmSignedQuotesLegendMe', $yearN - 1),
			);

			$graph->SetLegend($legend);

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
	 * Method to show box. Called when the box needs to be displayed.
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
	 * Check if a date selector has been provided in request (GET/POST),
	 * supporting both naming styles: prefixday/prefixmonth/prefixyear and prefix_day/prefix_month/prefix_year.
	 *
	 * @param string $prefix Prefix used in selectDate()
	 * @return bool
	 */
	protected function isDateProvidedInRequest($prefix)
	{
		$names = array(
			$prefix.'day', $prefix.'month', $prefix.'year',
			$prefix.'_day', $prefix.'_month', $prefix.'_year',
		);

		foreach ($names as $n) {
			$v = GETPOST($n, 'alphanohtml');
			if ($v !== '' && $v !== null) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get date from request for a selectDate() prefix.
	 * Supports both naming styles: prefixday/prefixmonth/prefixyear and prefix_day/prefix_month/prefix_year.
	 *
	 * @param string $prefix Prefix used in selectDate()
	 * @param int $default Default timestamp if not provided
	 * @return int Timestamp
	 */
	protected function getDateFromRequest($prefix, $default = 0)
	{
		$day = GETPOSTINT($prefix.'day');
		$month = GETPOSTINT($prefix.'month');
		$year = GETPOSTINT($prefix.'year');

		if (empty($day) && empty($month) && empty($year)) {
			$day = GETPOSTINT($prefix.'_day');
			$month = GETPOSTINT($prefix.'_month');
			$year = GETPOSTINT($prefix.'_year');
		}

		if (!empty($year) && !empty($month) && !empty($day)) {
			return dol_mktime(0, 0, 0, $month, $day, $year);
		}

		// Fallback if something else populated (rare)
		$ts = GETPOSTDATE($prefix);
		if (!empty($ts)) {
			return $ts;
		}

		return (int) $default;
	}

	/**
	 * Build month keys and labels for a range inside the current year.
	 *
	 * @param int $fromDate Timestamp
	 * @param int $toDate Timestamp
	 * @return array<int,array{key:string,label:string,month:int}>
	 */
	protected function buildMonthSequenceByRange($fromDate, $toDate)
	{
		$months = array();

		$y = (int) dol_print_date($fromDate, '%Y');
		$mStart = (int) dol_print_date($fromDate, '%m');
		$mEnd = (int) dol_print_date($toDate, '%m');

		for ($m = $mStart; $m <= $mEnd; $m++) {
			$ts = dol_mktime(12, 0, 0, $m, 1, $y);
			$months[] = array(
				'key' => dol_print_date($ts, '%Y-%m'),
				'label' => dol_print_date($ts, '%b'),
				'month' => $m,
			);
		}

		return $months;
	}

	/**
	 * Fetch signed quotes count grouped by month.
	 *
	 * Uses p.date_signature and signed/billed statuses (same base as signed turnover).
	 *
	 * User scope = author (creator) of the proposal: p.fk_user_author
	 *
	 * @param int $fromDate Timestamp start date
	 * @param int $toDate Timestamp end date
	 * @param int $userId 0 = all company, else filter
	 * @return array<string,int>
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

		return 
::contentReference[oaicite:0]{index=0}
