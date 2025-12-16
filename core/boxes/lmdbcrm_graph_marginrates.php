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
 * \file    lmdbcrm/core/boxes/lmdbcrm_graph_marginrates.php
 * \ingroup lmdbcrm
 * \brief   Graph widget for global margin rate (user and company).
 */

require_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

/**
 * Class to manage the margin rates graph box
 */
class lmdbcrm_graph_marginrates extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = 'lmdbcrmmarginrates';

	/**
	 * @var string Box icon (in configuration page)
	 */
	public $boximg = 'fa-percent';

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel = 'LmdbCrmMarginRatesTitle';

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
		global $langs, $conf, $user;

		$langs->loadLangs(array('lmdbcrm@lmdbcrm', 'propal'));

		$now = dol_now();

		$fromday = GETPOST('lmdbcrmmarg_fromday', 'int');
		$frommonth = GETPOST('lmdbcrmmarg_frommonth', 'int');
		$fromyear = GETPOST('lmdbcrmmarg_fromyear', 'int');
		$today = GETPOST('lmdbcrmmarg_today', 'int');
		$tomonth = GETPOST('lmdbcrmmarg_tomonth', 'int');
		$toyear = GETPOST('lmdbcrmmarg_toyear', 'int');

		$currentyear = (int) dol_print_date($now, '%Y');

		$fromdate = dol_mktime(0, 0, 0, empty($frommonth) ? 1 : $frommonth, empty($fromday) ? 1 : $fromday, empty($fromyear) ? $currentyear : $fromyear);
		$todate = dol_mktime(23, 59, 59, empty($tomonth) ? 12 : $tomonth, empty($today) ? 31 : $today, empty($toyear) ? $currentyear : $toyear);

		if ($fromdate > $todate) {
			$tmp = $fromdate;
			$fromdate = $todate;
			$todate = $tmp;
		}

		$this->info_box_head = array(
			'text' => $langs->trans('LmdbCrmMarginRatesTitle'),
			'limit' => 0,
			'subpicto' => 'help',
			'subtext'  => dol_escape_htmltag($langs->transnoentitiesnoconv('LmdbCrmMarginRatesTooltip')),
			'subclass' => 'classfortooltip',
		);

		$userData = $this->fetchMarginData($fromdate, $todate, (int) $user->id);
		$companyData = $this->fetchMarginData($fromdate, $todate, 0);

		$form = new Form($this->db);

		$filterform = '<form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" class="nocellnopadding">';
		$filterform .= '<div class="center">';
		$filterform .= $langs->trans('LmdbCrmMarginPeriodLabel').' ';
		$filterform .= $form->selectDate($fromdate, 'lmdbcrmmarg_from', 0, 0, 0, '', 1, 1);
		$filterform .= $langs->trans('to').' ';
		$filterform .= $form->selectDate($todate, 'lmdbcrmmarg_to', 0, 0, 1, '', 1, 1);
		$filterform .= '<input type="submit" class="button smallpaddingimp" value="'.$langs->trans('Refresh').'">';
		$filterform .= '</div>';
		$filterform .= '</form>';

		$userGraph = $this->renderMarginGraph($userData, 'user', $langs->trans('LmdbCrmMarginUserLabel'));
		$companyGraph = $this->renderMarginGraph($companyData, 'company', $langs->trans('LmdbCrmMarginCompanyLabel'));

		$graphsHtml = '<div class="center">'.$filterform.'<div class="flexcontainer wrap center">';
		$graphsHtml .= '<div class="center" style="min-width:340px;">'.$userGraph.'</div>';
		$graphsHtml .= '<div class="center" style="min-width:340px;">'.$companyGraph.'</div>';
		$graphsHtml .= '</div></div>';

		$this->info_box_contents = array();
		$this->info_box_contents[] = array(
			0 => array(
				'td' => 'class="center"',
				'asis' => 1,
				'align' => 'center',
				'css' => 'nohover center',
				'url' => '',
				'color' => '',
				'rowspan' => 1,
				'colspan' => 1,
				'larrow' => '',
				'rarrow' => '',
				'logo' => '',
				'border' => '',
				'position' => '',
				'text' => $graphsHtml,
			),
		);
	}

	/**
	 * Method to show box. Called when the box needs to be displayed.
	 *
	 * @param ?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:string,target?:string}> $head Array with properties of box title
	 * @param ?array<array{tr?:string,td?:string,target?:string,text?:string,moretext?:string,url?:string,logo?:string,img?:string,color?:string}> $contents Array with properties of box lines
	 * @param int<0,1> $nooutput No print, only return string
	 * @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

	/**
	 * Build a pie graph with margin amounts. Percent shown is the margin rate (margin / turnover).
	 *
	 * @param array{turnover:float,cost:float,margin:float} $data Margin data
	 * @param string $suffix Suffix for temporary file names
	 * @param string $title Title for the graph
	 * @return string
	 */
	protected function renderMarginGraph($data, $suffix, $title)
	{
		global $langs, $conf;

		$turnover = (float) $data['turnover'];
		$cost = (float) $data['cost'];
		$margin = (float) $data['margin'];

		if ($turnover <= 0) {
			return '<div class="opacitymedium">'.$langs->trans('LmdbCrmMarginNoData').'</div>';
		}

		$rate = round(($margin / $turnover) * 100, 2);

		$currencySymbol = '';
		if (!empty($conf->currency)) {
			$currencySymbol = $langs->getCurrencySymbol($conf->currency);
		}

		$turnoverStr = trim(price($turnover, 0, $langs, 1, -1, 2).' '.$currencySymbol);
		$marginStr = trim(price($margin, 0, $langs, 1, -1, 2).' '.$currencySymbol);

		$label = '<div class="center"><strong>'.$title.'</strong><br>'.dol_escape_htmltag($rate).' %</div>';
		$label .= '<div class="center opacitymedium">';
		$label .= $langs->trans('LmdbCrmMarginTurnoverLabel').': '.dol_escape_htmltag($turnoverStr);
		$label .= ' &nbsp;|&nbsp; '.$langs->trans('LmdbCrmMarginAmountLabel').': '.dol_escape_htmltag($marginStr);
		$label .= '</div>';

		// Pie graph cannot handle negative values reliably
		if ($margin < 0 || $cost < 0) {
			$label .= '<div class="center opacitymedium">'.$langs->trans('LmdbCrmMarginNegativeNoGraph').'</div>';
			return $label;
		}

		$graph = new DolGraph();

		// Legend includes amounts => "montant dans les valeurs"
		$labelMargin = $langs->transnoentities('LmdbCrmMarginSliceMargin').' ('.$currencySymbol.')';
		$labelCost = $langs->transnoentities('LmdbCrmMarginSliceCost').' ('.$currencySymbol.')';

		$graph->SetData(array(
			array($labelMargin, $margin),
			array($labelCost, $cost),
		));
		$graph->SetDataColor(array('#4caf50', '#c0c0c0'));
		$graph->setShowLegend(1);
		$graph->setShowPercent(1);

		// According to Dolibarr version, DolGraph can also show absolute values on the chart
		if (method_exists($graph, 'setShowValues')) {
			$graph->setShowValues(1);
		}

		$graph->SetType(array('pie'));
		$graph->setHeight('280');
		$graph->setWidth('340');

		$graphid = 'lmdbcrmmarg_'.$suffix.'_e'.((int) $conf->entity);
		$graph->draw($graphid);

		return $label.$graph->show(0);
	}

	/**
	 * Fetch margin data for a given user or the company.
	 * Note: period filter is based on proposal creation date (p.datec) like the conversion widget.
	 *
	 * @param int $fromdate Timestamp start date
	 * @param int $todate Timestamp end date
	 * @param int $userid User identifier (0 for all users)
	 * @return array{turnover:float,cost:float,margin:float}
	 */
	protected function fetchMarginData($fromdate, $todate, $userid = 0)
	{
		$turnover = 0.0;
		$cost = 0.0;

		$signedStatus = (defined('Propal::STATUS_SIGNED') ? Propal::STATUS_SIGNED : 2);
		$billedStatus = (defined('Propal::STATUS_BILLED') ? Propal::STATUS_BILLED : 4);

		// Try with different cost fields for retro-compatibility.
		$costfields = array('pd.buy_price_ht', 'pd.pa_ht');

		foreach ($costfields as $costfield) {
			$sql = "SELECT SUM(pd.total_ht) as turnover,";
			$sql .= " SUM(CASE WHEN ".$costfield." IS NOT NULL THEN (".$costfield." * pd.qty) ELSE 0 END) as cost";
			$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."propaldet as pd ON (pd.fk_propal = p.rowid)";
			$sql .= " WHERE p.entity IN (".getEntity('propal').")";
			$sql .= " AND p.datec IS NOT NULL";
			$sql .= " AND p.datec >= '".$this->db->idate($fromdate)."'";
			$sql .= " AND p.datec <= '".$this->db->idate($todate)."'";
			$sql .= " AND p.fk_statut IN (".((int) $signedStatus).",".((int) $billedStatus).")";

			if (!empty($userid)) {
				$sql .= " AND p.fk_user_author = " . ((int) $userid);
			}

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$turnover = (float) $obj->turnover;
					$cost = (float) $obj->cost;
				}
				$this->db->free($resql);
				break;
			}
		}

		$margin = $turnover - $cost;

		return array('turnover' => $turnover, 'cost' => $cost, 'margin' => $margin);
	}
}
