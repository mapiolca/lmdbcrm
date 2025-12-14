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
 * \file    lmdbcrm/core/boxes/lmdbcrm_graph_conversionrates.php
 * \ingroup lmdbcrm
 * \brief   Graph widget for conversion rates (user and company).
 */

require_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

/**
 * Class to manage the conversion rates graph box
 */
class lmdbcrm_graph_conversionrates extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = 'lmdbcrmconversionrates';

	/**
	 * @var string Box icon (in configuration page)
	 */
	public $boximg = 'fa-chart-pie';

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel = 'LmdbCrmConversionRatesTitle';

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

		$fromday = GETPOST('lmdbcrmconv_fromday', 'int');
		$frommonth = GETPOST('lmdbcrmconv_frommonth', 'int');
		$fromyear = GETPOST('lmdbcrmconv_fromyear', 'int');
		$today = GETPOST('lmdbcrmconv_today', 'int');
		$tomonth = GETPOST('lmdbcrmconv_tomonth', 'int');
		$toyear = GETPOST('lmdbcrmconv_toyear', 'int');

		$currentyear = (int) dol_print_date($now, '%Y');

		$fromdate = dol_mktime(0, 0, 0, empty($frommonth) ? 1 : $frommonth, empty($fromday) ? 1 : $fromday, empty($fromyear) ? $currentyear : $fromyear);
		$todate = dol_mktime(23, 59, 59, empty($tomonth) ? 12 : $tomonth, empty($today) ? 31 : $today, empty($toyear) ? $currentyear : $toyear);

		if ($fromdate > $todate) {
			$tmp = $fromdate;
			$fromdate = $todate;
			$todate = $tmp;
		}

		$form = new Form($this->db);

		$this->info_box_head = array(
			'text' => $langs->trans('LmdbCrmConversionRatesTitle'),
			'limit' => 0,
		);

		$userData = $this->fetchConversionData($fromdate, $todate, (int) $user->id);
		$companyData = $this->fetchConversionData($fromdate, $todate, 0);

		$filterform = '<form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" class="nocellnopadding">';
		$filterform .= '<div class="center">';
		$filterform .= $langs->trans('LmdbCrmConversionPeriodLabel').' ';
		$filterform .= $form->selectDate($fromdate, 'lmdbcrmconv_from', 0, 0, 1, '', 1, 1, 0, '', '', 1);
		$filterform .= $langs->trans('to').' ';
		$filterform .= $form->selectDate($todate, 'lmdbcrmconv_to', 0, 0, 1, '', 1, 1, 0, '', '', 1);
		$filterform .= '<input type="submit" class="button smallpaddingimp" value="'.$langs->trans('Refresh').'">';
		$filterform .= '</div>';
		$filterform .= '</form>';

		$userGraph = $this->renderPieGraph($userData, 'user', $langs->trans('LmdbCrmConversionUserLabel'));
		$companyGraph = $this->renderPieGraph($companyData, 'company', $langs->trans('LmdbCrmConversionCompanyLabel'));

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
	 * Build a pie graph with conversion rates.
	 *
	 * @param array{total:int<0,max>,signed:int<0,max>} $data Conversion data
	 * @param string $suffix Suffix for temporary file names
	 * @param string $title Title for the graph
	 * @return string
	 */
	protected function renderPieGraph($data, $suffix, $title)
	{
		global $langs, $conf;

		$total = (int) $data['total'];
		$signed = (int) $data['signed'];
		$unsigned = max(0, $total - $signed);

		if ($total <= 0) {
			return '<div class="opacitymedium">'.$langs->trans('LmdbCrmConversionNoData').'</div>';
		}

		$graph = new DolGraph();
		$graph->SetData(array(
			array($langs->trans('LmdbCrmConversionSigned'), $signed),
			array($langs->trans('LmdbCrmConversionUnsigned'), $unsigned),
		));
		$graph->SetDataColor(array('76a7fa', 'c0c0c0'));
		$graph->setShowLegend(1);
		$graph->setShowPercent(1);
		$graph->SetType(array('pie'));
		$graph->setHeight('280');
		$graph->setWidth('340');

		$dir = $conf->user->dir_temp;
		if (!empty($conf->multicompany->enabled) && !empty($conf->entity)) {
			$dir .= '/'.$conf->entity;
		}
		dol_mkdir($dir);

		$filename = 'lmdbcrm_conversion_'.$suffix.'_'.dol_print_date(dol_now(), 'dayhourlog').'.png';
		$file = $dir.'/'.$filename;
		$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=apercu&file='.urlencode(basename($dir).'/'.$filename);

		$graph->draw($file, $fileurl);

		$percent = round(($signed / $total) * 100, 2);
		$label = '<div class="center"><strong>'.$title.'</strong><br>'.dol_escape_htmltag($percent).' %</div>';

		return $label.$graph->show($fileurl, 0, '', 0, 1);
	}

	/**
	 * Fetch conversion data for a given user or the company.
	 *
	 * @param int $fromdate Timestamp start date
	 * @param int $todate Timestamp end date
	 * @param int $userid User identifier (0 for all users)
	 * @return array{total:int<0,max>,signed:int<0,max>}
	 */
	protected function fetchConversionData($fromdate, $todate, $userid = 0)
	{
		$total = 0;
		$signed = 0;

		$sql = "SELECT COUNT(p.rowid) as total, SUM(CASE WHEN p.fk_statut = ".Propal::STATUS_SIGNED." THEN 1 ELSE 0 END) as signed";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		$sql .= " WHERE p.entity IN (".getEntity('propal').")";
		$sql .= " AND p.datec IS NOT NULL";
		$sql .= " AND p.datec >= '".$this->db->idate($fromdate)."'";
		$sql .= " AND p.datec <= '".$this->db->idate($todate)."'";
		if (!empty($userid)) {
			$sql .= " AND p.fk_user_author = " . ((int) $userid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$total = (int) $obj->total;
				$signed = (int) $obj->signed;
			}
			$this->db->free($resql);
		}

		return array('total' => $total, 'signed' => $signed);
	}
}
