<?php
/* Copyright (C) 2004-2017      Laurent Destailleur                     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024      Frederic France                         <frederic.france@free.fr>
 * Copyright (C) 2026           Pierre Ardoin                           <developpeur@lesmetiersdubatiment.fr>
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
 * \file    lmdbcrm/core/boxes/lmdbcrm_orders_delivered_to_bill.php
 * \ingroup lmdbcrm
 * \brief   Widget for latest delivered customer orders not yet billed.
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * Class to manage the delivered unbilled customer orders box.
 */
class lmdbcrm_orders_delivered_to_bill extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = 'lmdbcrmordersdeliveredtobill';

	/**
	 * @var string Box icon (in configuration page)
	 */
	public $boximg = 'object_order';

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel = 'LmdbCrmDeliveredUnbilledOrdersBoxLabel';

	/**
	 * @var string Box language file if it needs a specific language file.
	 */
	public $lang = 'lmdbcrm@lmdbcrm';

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array('lmdbcrm', 'commande');

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $user;

		parent::__construct($db, $param);

		$this->db = $db;
		$this->param = $param;
		$this->hidden = !$user->hasRight('commande', 'lire');
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int<0,max> $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $langs, $user;

		$langs->loadLangs(array('lmdbcrm@lmdbcrm', 'orders', 'companies'));

		$this->max = ($max > 0 ? $max : 5);

		$deliveredStatus = defined('Commande::STATUS_CLOSED') ? Commande::STATUS_CLOSED : 3;
		$listurl = DOL_URL_ROOT.'/commande/list.php?search_status='.urlencode((string) $deliveredStatus).'&search_billed=0&sortfield=c.tms&sortorder=DESC';
		$total = $user->hasRight('commande', 'lire') ? $this->fetchDeliveredUnbilledTotal($deliveredStatus) : 0;
		$totalBadge = '<a class="paddingleft" href="'.$listurl.'"><span class="badge">'.$total.'</span></a>';
		$text = $langs->trans('LmdbCrmDeliveredUnbilledOrdersTitle', $this->max);

		$this->info_box_head = array(
			//'text' => $text.$totalBadge,
			'text' => $text.'<a href="'.$listurl.'" class="badge badge-info">'$total'</a>',
			'limit' => 0,
			'subpicto' => 'help',
			'subtext' => dol_escape_htmltag($langs->transnoentitiesnoconv('LmdbCrmDeliveredUnbilledOrdersTooltip')),
			'subclass' => 'classfortooltip',
		);

		$this->info_box_contents = array();

		if (!$user->hasRight('commande', 'lire')) {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans('ReadPermissionNotAllowed').'</span>',
			);
			return;
		}

		$commandestatic = new Commande($this->db);
		$societestatic = new Societe($this->db);

		$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
		$sql .= ", s.code_client, s.code_compta as code_compta_client, s.client";
		$sql .= ", s.logo, s.email, s.entity";
		$sql .= ", c.ref, c.tms, c.rowid, c.date_commande, c.ref_client";
		$sql .= ", c.fk_statut, c.facture, c.total_ht, c.total_tva, c.total_ttc";
		$sql .= $this->buildDeliveredUnbilledFromWhere($deliveredStatus);
		$sql .= " ORDER BY c.tms DESC, c.ref DESC";
		$sql .= $this->db->plimit($this->max, 0);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$line = 0;

			while ($line < $num) {
				$objp = $this->db->fetch_object($resql);
				$date = $this->db->jdate($objp->date_commande);
				$datem = $this->db->jdate($objp->tms);

				$commandestatic->id = (int) $objp->rowid;
				$commandestatic->ref = $objp->ref;
				$commandestatic->ref_client = $objp->ref_client;
				$commandestatic->total_ht = $objp->total_ht;
				$commandestatic->total_tva = $objp->total_tva;
				$commandestatic->total_ttc = $objp->total_ttc;
				$commandestatic->date = $date;
				$commandestatic->date_modification = $datem;

				$societestatic->id = (int) $objp->socid;
				$societestatic->name = $objp->name;
				$societestatic->code_client = $objp->code_client;
				$societestatic->code_compta = $objp->code_compta_client;
				$societestatic->code_compta_client = $objp->code_compta_client;
				$societestatic->client = $objp->client;
				$societestatic->logo = $objp->logo;
				$societestatic->email = $objp->email;
				$societestatic->entity = $objp->entity;

				$this->info_box_contents[$line][] = array(
					'td' => 'class="nowraponall"',
					'text' => $commandestatic->getNomUrl(1),
					'asis' => 1,
				);

				$this->info_box_contents[$line][] = array(
					'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
					'text' => $societestatic->getNomUrl(1),
					'asis' => 1,
				);

				$this->info_box_contents[$line][] = array(
					'td' => 'class="nowraponall right amount"',
					'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
				);

				$this->info_box_contents[$line][] = array(
					'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans('DateModification').': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
					'text' => dol_print_date($datem, 'day', 'tzuserrel'),
				);

				$this->info_box_contents[$line][] = array(
					'td' => 'class="right" width="18"',
					'text' => $commandestatic->LibStatut($objp->fk_statut, $objp->facture, 3),
				);

				$line++;
			}

			if ($num == 0) {
				$this->info_box_contents[0][0] = array(
					'td' => 'class="center"',
					'text' => '<span class="opacitymedium">'.$langs->trans('LmdbCrmDeliveredUnbilledOrdersEmpty').'</span>',
				);
			}

			$this->db->free($resql);
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => '',
				'maxlength' => 500,
				'text' => dol_escape_htmltag($this->db->lasterror().' sql='.$sql),
			);
		}
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
	 * Fetch total number of delivered customer orders not yet billed.
	 *
	 * @param int $deliveredStatus Dolibarr delivered order status
	 * @return int
	 */
	protected function fetchDeliveredUnbilledTotal($deliveredStatus)
	{
		$sql = "SELECT COUNT(c.rowid) as nb";
		$sql .= $this->buildDeliveredUnbilledFromWhere($deliveredStatus);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return 0;
		}

		$obj = $this->db->fetch_object($resql);
		$total = $obj ? (int) $obj->nb : 0;
		$this->db->free($resql);

		return $total;
	}

	/**
	 * Build shared FROM and WHERE clauses for delivered unbilled customer orders.
	 *
	 * @param int $deliveredStatus Dolibarr delivered order status
	 * @return string
	 */
	protected function buildDeliveredUnbilledFromWhere($deliveredStatus)
	{
		global $user;

		$sql = " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE c.fk_soc = s.rowid";
		$sql .= " AND c.entity IN (".getEntity('commande').")";
		$sql .= " AND c.fk_statut = ".((int) $deliveredStatus);
		$sql .= " AND c.facture = 0";
		if (empty($user->socid) && !$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		if ($user->socid) {
			$sql .= " AND s.rowid = ".((int) $user->socid);
		}

		return $sql;
	}
}
