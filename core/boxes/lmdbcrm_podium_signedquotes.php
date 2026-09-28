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
 * \file    lmdbcrm/core/boxes/lmdbcrm_podium_signedquotes.php
 * \ingroup lmdbcrm
 * \brief   Podium widget for signed proposals on last 30 days.
 */

require_once __DIR__.'/../../class/lmdbcrmbox.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/**
 * Class to manage the signed proposals podium box
 */
class lmdbcrm_podium_signedquotes extends LmdbCrmBox
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = 'lmdbcrmsignedquotespodium';

	/**
	 * @var string Box icon (in configuration page)
	 */
	public $boximg = 'fa-trophy';

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel = 'LmdbCrmSignedQuotesPodiumTitle';

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
		$this->hidden = !isModEnabled('lmdbcrm') || !isModEnabled('propal') || !empty($user->socid)
			|| !$user->hasRight('propal', 'lire')
			|| (!$user->hasRight('lmdbcrm', 'widgets', 'readall') && !$user->hasRight('lmdbcrm', 'widgets', 'read'));
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int<0,max> $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 3)
	{
		global $langs, $conf, $user;

		$langs->loadLangs(array('main', 'lmdbcrm@lmdbcrm', 'propal', 'users'));

		$this->info_box_head = array();
		$this->info_box_contents = array();
		$this->lmdbcrmDataLoaded = false;
		$this->hidden = !isModEnabled('lmdbcrm') || !isModEnabled('propal') || !empty($user->socid)
			|| !$user->hasRight('propal', 'lire')
			|| (!$user->hasRight('lmdbcrm', 'widgets', 'readall') && !$user->hasRight('lmdbcrm', 'widgets', 'read'));
		if ($this->hidden) {
			return;
		}
		$this->lmdbcrmDataLoaded = true;
		$this->lmdbcrmLoadedAll = $user->hasRight('lmdbcrm', 'widgets', 'readall');

		$this->max = ($max > 0 ? $max : 3);

		$this->info_box_head = array(
			'text' => $langs->trans('LmdbCrmSignedQuotesPodiumTitle'),
			'limit' => 0,
			'subpicto' => 'help',
			'subtext'  => dol_escape_htmltag($langs->transnoentitiesnoconv('LmdbCrmSignedQuotesPodiumTooltip')),
			'subclass' => 'classfortooltip',
		);

		$this->info_box_contents = array();
		$this->info_box_contents[] = array(
			0 => array(
				'td' => 'class="center"',
				'asis' => 1,
				'css' => 'liste_titre center',
				'align' => 'center',
				'url' => '',
				'text' => '#',
			),
			1 => array(
				'td' => 'class="left"',
				'asis' => 1,
				'css' => 'liste_titre left',
				'align' => 'left',
				'url' => '',
				'color' => '',
				'text' => $langs->trans('LmdbCrmSignedQuotesPodiumUser'),
			),
			2 => array(
				'td' => 'class="right"',
				'asis' => 1,
				'css' => 'liste_titre right',
				'align' => 'right',
				'url' => '',
				'color' => '',
				'text' => $langs->trans('LmdbCrmSignedQuotesPodiumCountTitle'),
			),
		);

		$now = dol_now();
		$fromDate = dol_time_plus_duree($now, -30, 'd');

		$sql = "SELECT p.fk_user_author as userid, COUNT(p.rowid) as qty";
		foreach (array('lastname', 'firstname', 'login', 'photo', 'statut') as $field) {
			$sql .= $user->hasRight('lmdbcrm', 'widgets', 'readall') ? ", u.".$field
				: ", CASE WHEN p.fk_user_author = ".((int) $user->id)." THEN u.".$field." ELSE NULL END as ".$field;
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = p.fk_user_author";
		$sql .= " WHERE p.entity IN (".getEntity('propal').")";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $user->id).")";
		}
		$sql .= " AND p.fk_statut = ".Propal::STATUS_SIGNED;
		$sql .= " AND p.fk_user_author IS NOT NULL";
		$sql .= " AND p.date_signature IS NOT NULL";
		$sql .= " AND p.date_signature >= '".$this->db->idate($fromDate)."'";
		$sql .= " GROUP BY p.fk_user_author, u.lastname, u.firstname, u.login, u.photo, u.statut";
		$sql .= " ORDER BY qty DESC, p.fk_user_author ASC";
		// Scan the scoped ranking once to retain the viewer even below the podium.

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$rank = 1;
				$ownRowFound = false;
				while ($obj = $this->db->fetch_object($resql)) {
					if ($rank > $this->max && (int) $obj->userid !== (int) $user->id) {
						$rank++;
						continue;
					}
					if (!$user->hasRight('lmdbcrm', 'widgets', 'readall') && (int) $obj->userid !== (int) $user->id) {
						if ($rank <= $this->max) {
							$this->info_box_contents[] = array(
								array('text' => (string) $rank, 'align' => 'center'),
								array('text' => $langs->trans('LmdbCrmOtherSalesRep')),
								array('text' => '<span class="lmdbcrm-masked-value" aria-label="'.dol_escape_htmltag($langs->trans('LmdbCrmDataMasked')).'"></span>', 'asis' => 1),
							);
						}
						$rank++;
						continue;
					}

					if ((int) $obj->userid === (int) $user->id) $ownRowFound = true;
					$userlink = dol_escape_htmltag($langs->trans('Unknown'));
					$photohtml = '';
					if (!empty($obj->userid)) {
						$tmpuser = new User($this->db);
						$tmpuser->id = (int) $obj->userid;
						$tmpuser->lastname = $obj->lastname;
						$tmpuser->firstname = $obj->firstname;
						$tmpuser->login = $obj->login;
						$tmpuser->photo = $obj->photo;
						$tmpuser->statut = $obj->statut;
						$userlink = $tmpuser->getNomUrl(-1);
						if (!empty($tmpuser->photo)) {
							$photourl = dol_buildpath('/viewimage.php', 1).'?modulepart=userphoto&file='.urlencode($tmpuser->photo);
							$photohtml = '<img class="inline-block" style="max-height:32px;max-width:32px;border-radius:50%;margin-right:6px;" src="'.$photourl.'" alt="'.$langs->trans('Photo').'">';
						}
					}

					$signedquotesqty = dol_escape_htmltag($obj->qty);
					if (!empty($obj->login)) {
						$signedquoteslisturl = dol_buildpath('/comm/propal/list.php', 1).'?search_status='.urlencode((string) (Propal::STATUS_SIGNED.','.Propal::STATUS_BILLED)).'&search_login='.urlencode((string) $obj->login);
						$signedquotesqty = '<a href="'.$signedquoteslisturl.'">'.$signedquotesqty.'</a>';
					}

					$this->info_box_contents[] = array(
						0 => array(
							'td' => 'class="center"',
							'align' => 'center',
							'asis' => 1,
							'text' => '<strong>'.$rank.'</strong>',
						),
						1 => array(
							'td' => 'class="left"',
							'asis' => 1,
							'text' => $userlink,
						),
						2 => array(
							'td' => 'class="right"',
							'align' => 'right',
							'asis' => 1,
							'text' => $signedquotesqty,
						),
					);
					$rank++;
				}
				if (!$user->hasRight('lmdbcrm', 'widgets', 'readall') && !$ownRowFound) {
					$this->info_box_contents[] = array(array('text' => $langs->trans('LmdbCrmOwnNoSignedProposal'), 'td' => 'colspan="3"'));
				}
			} else {
				$this->info_box_contents[] = array(
					array(
						'td' => 'class="center opacitymedium" colspan="3"',
						'text' => $langs->trans('NoRecordFound'),
					),
				);
			}

			$this->db->free($resql);
		} else {
			$this->info_box_contents[] = array(
				0 => array(
					'td' => 'class="center" colspan="3"',
					'asis' => 1,
					'text' => dol_escape_htmltag($this->db->lasterror()),
				),
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
		global $user;

		$this->hidden = !isModEnabled('lmdbcrm') || !isModEnabled('propal') || !empty($user->socid)
			|| !$user->hasRight('propal', 'lire')
			|| (!$user->hasRight('lmdbcrm', 'widgets', 'readall') && !$user->hasRight('lmdbcrm', 'widgets', 'read'));
		if ($this->hidden) {
			$this->info_box_head = array();
			$this->info_box_contents = array();
			$this->lmdbcrmDataLoaded = false;
			return '';
		}
		// Never reuse a previously loaded scope after a permission transition.
		if ($this->lmdbcrmLoadedAll !== $user->hasRight('lmdbcrm', 'widgets', 'readall')) {
			$this->info_box_head = array();
			$this->info_box_contents = array();
			$this->lmdbcrmDataLoaded = false;
		}
		if (!$this->lmdbcrmDataLoaded) {
			return '';
		}
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}
