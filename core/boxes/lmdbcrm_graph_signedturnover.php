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
 * \file    lmdbcrm/core/boxes/lmdbcrm_graph_signedturnover.php
 * \ingroup lmdbcrm
 * \brief   Line graph widget for signed turnover by month on current fiscal year.
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

/**
 * Class to manage the signed turnover fiscal year graph box
 */
class lmdbcrm_graph_signedturnover extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = 'lmdbcrmsignedturnovergraph';

	/**
	 * @var string Box icon (in configuration page)
	 */
	public $boximg = 'fa-line-chart';

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel = 'LmdbCrmSignedTurnoverCurveTitle';

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
	public function __construct($db, $param)
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
		global $conf, $langs;

		$langs->loadLangs(array('lmdbcrm@lmdbcrm', 'propal'));

		$debug = GETPOSTINT('debug_lmdbcrmsignedturnover');

		$range = $this->getCurrentFiscalYearRange();

		$monthlyData = $this->fetchSignedTurnoverByMonth($range['start'], $range['end']);
		$months = $this->buildMonthSequence($range['start']);

		$graphData = array();
		$totalAmount = 0.0;
		foreach ($months as $monthInfo) {
			$key = $monthInfo['key'];
			$value = 0.0;
			if (isset($monthlyData[$key])) {
				$value = (float) $monthlyData[$key];
			}
			$totalAmount += $value;
			$graphData[] = array($monthInfo['label'], $value);
		}

		if ($debug) {
			var_dump(array(
				'debug_scope' => 'lmdbcrm_graph_signedturnover::loadBox',
				'fiscal_range' => $range,
				'monthlyData' => $monthlyData,
				'graphData' => $graphData,
				'totalAmount' => $totalAmount,
			));
		}

		$this->info_box_head = array(
			'text' => $langs->trans('LmdbCrmSignedTurnoverCurveTitle'),
			'limit' => 0,
			'subpicto' => 'help',
			'subtext'  => dol_escape_htmltag($langs->transnoentitiesnoconv('LmdbCrmSignedTurnoverCurveTooltip')),
			'subclass' => 'classfortooltip',
		);

		$contentHtml = '';
		$contentHtml .= '<div class="center opacitymedium">'.$langs->trans('LmdbCrmSignedTurnoverCurveFiscalYear', dol_escape_htmltag($range['label'])).'</div>';

		if ($totalAmount <= 0) {
			$contentHtml .= '<div class="center opacitymedium">'.$langs->trans('LmdbCrmSignedTurnoverCurveNoData').'</div>';
		} else {
			$graph = new DolGraph();
			$graph->SetData($graphData);
			$graph->SetLegend(array($langs->trans('LmdbCrmSignedTurnoverCurveLegend')));
			$graph->SetDataColor(array('#2e78c2'));
			$graph->SetType(array('lines'));
			$graph->setHeight('320');
			$graph->setWidth('740');
			$graph->setShowLegend(1);
			$graph->setMinValue(0);

			$graphId = 'lmdbcrmsignedturnoverfy_e'.((int) $conf->entity);
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
	 * @param ?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:?string,picto?:string,nbcol?:int,limit?:int,subclass?:string,graph?:int<0,1>,target?:string}> $head Array with properties of box title
	 * @param ?array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:int,asis?:int<0,1>,asis2?:int<0,1>,align?:string,css?:string,color?:string}> $contents Array with properties of box lines
	 * @param int<0,1> $nooutput No print, only return string
	 * @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

	/**
	 * Return start/end timestamps and label for the current fiscal year.
	 *
	 * @return array{start:int,end:int,label:string}
	 */
	protected function getCurrentFiscalYearRange()
	{
		$now = dol_now();

		$fiscalStartMonth = (int) getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		if ($fiscalStartMonth < 1 || $fiscalStartMonth > 12) {
			$fiscalStartMonth = 1;
		}

		$currentYear = (int) dol_print_date($now, '%Y');
		$currentMonth = (int) dol_print_date($now, '%m');

		$startYear = $currentYear;
		if ($currentMonth < $fiscalStartMonth) {
			$startYear--;
		}

		$startDate = dol_mktime(0, 0, 0, $fiscalStartMonth, 1, $startYear);

		$endMonth = $fiscalStartMonth - 1;
		if ($endMonth <= 0) {
			$endMonth = 12;
		}
		$endYear = $startYear + ($fiscalStartMonth > 1 ? 1 : 0);
		$endDay = dol_get_last_day($endYear, $endMonth, 0);
		$endDate = dol_mktime(23, 59, 59, $endMonth, $endDay, $endYear);
		if (empty($endDate)) {
			$endDate = dol_time_plus_duree($startDate, 12, 'm') - 1;
		}

		$startLabelYear = dol_print_date($startDate, '%Y');
		$endLabelYear = dol_print_date($endDate, '%Y');
		$label = $startLabelYear;
		if ($startLabelYear !== $endLabelYear) {
			$label = $startLabelYear.'-'.$endLabelYear;
		}

		return array(
			'start' => $startDate,
			'end' => $endDate,
			'label' => $label,
		);
	}

	/**
	 * Build month keys and labels for a 12-month fiscal period.
	 *
	 * @param int $startDate Timestamp of fiscal start
	 * @return array<int,array{key:string,label:string}>
	 */
	protected function buildMonthSequence($startDate)
	{
		global $langs;

		$months = array();
		for ($i = 0; $i < 12; $i++) {
			$current = dol_time_plus_duree($startDate, $i, 'm');
			$key = dol_print_date($current, '%Y-%m');
			$label = dol_print_date($current, '%b %y');
			$months[] = array(
				'key' => $key,
				'label' => $label,
			);
		}

		return $months;
	}

	/**
	 * Fetch signed turnover grouped by month.
	 *
	 * @param int $fromDate Timestamp start date
	 * @param int $toDate Timestamp end date
	 * @return array<string,float>
	 */
	protected function fetchSignedTurnoverByMonth($fromDate, $toDate)
	{
		$data = array();

		$signedStatus = (defined('Propal::STATUS_SIGNED') ? Propal::STATUS_SIGNED : 2);
		$billedStatus = (defined('Propal::STATUS_BILLED') ? Propal::STATUS_BILLED : 4);
		$debug = GETPOSTINT('debug_lmdbcrmsignedturnover');

		$sql = "SELECT YEAR(p.date_signature) as y, MONTH(p.date_signature) as m, SUM(p.total_ht) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		$sql .= " WHERE p.entity IN (".getEntity('propal').")";
		$sql .= " AND p.fk_statut IN (".((int) $signedStatus).",".((int) $billedStatus).")";
		$sql .= " AND p.date_signature IS NOT NULL";
		$sql .= " AND p.date_signature >= '".$this->db->idate($fromDate)."'";
		$sql .= " AND p.date_signature <= '".$this->db->idate($toDate)."'";
		$sql .= " GROUP BY YEAR(p.date_signature), MONTH(p.date_signature)";

		if ($debug) {
			var_dump(array(
				'debug_scope' => 'lmdbcrm_graph_signedturnover::fetchSignedTurnoverByMonth',
				'sql' => $sql,
				'fromDate' => $fromDate,
				'toDate' => $toDate,
				'signedStatus' => $signedStatus,
				'billedStatus' => $billedStatus,
			));
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$key = sprintf('%04d-%02d', (int) $obj->y, (int) $obj->m);
				$data[$key] = (float) $obj->amount;
			}
			$this->db->free($resql);
		}

		if ($debug) {
			var_dump(array(
				'debug_scope' => 'lmdbcrm_graph_signedturnover::fetchSignedTurnoverByMonth_results',
				'results' => $data,
			));
		}

		return $data;
	}
}
