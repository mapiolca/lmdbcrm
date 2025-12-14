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

dol_include_once('/core/boxes/modules_boxes.php');
dol_include_once('/comm/propal/class/propal.class.php');
dol_include_once('/user/class/user.class.php');

/**
 * Class to manage the signed proposals podium box
 */
class lmdbcrm_podium_signedquotes extends ModeleBoxes
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

		$this->param = $param;
		$this->hidden = empty($user->rights->propal->lire);
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int<0,max> $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 3)
	{
		global $langs;

		$langs->loadLangs(array('lmdbcrm@lmdbcrm', 'propal', 'users'));

		$this->max = ($max > 0 ? $max : 3);

		$this->info_box_head = array(
			'text' => $langs->trans('LmdbCrmSignedQuotesPodiumTitle'),
			'limit' => 0,
		);

		$this->info_box_contents = array();

		$now = dol_now();
		$fromDate = dol_time_plus_duree($now, -30, 'd');

		$sql = "SELECT p.fk_user_author as userid, COUNT(p.rowid) as qty, u.lastname, u.firstname, u.login, u.photo, u.statut";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = p.fk_user_author";
		$sql .= " WHERE p.entity IN (".getEntity('propal').")";
		$sql .= " AND p.fk_statut = ".Propal::STATUS_SIGNED;
		$sql .= " AND p.fk_user_author IS NOT NULL";
		$sql .= " AND p.date_cloture IS NOT NULL";
		$sql .= " AND p.date_cloture >= '".$this->db->idate($fromDate)."'";
		$sql .= " GROUP BY p.fk_user_author, u.lastname, u.firstname, u.login, u.photo, u.statut";
		$sql .= " ORDER BY qty DESC";
		$sql .= $this->db->plimit($this->max);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$rank = 1;
				while ($obj = $this->db->fetch_object($resql)) {
					$userlink = $langs->trans('Unknown');
					if (!empty($obj->userid)) {
						$tmpuser = new User($this->db);
						$tmpuser->id = (int) $obj->userid;
						$tmpuser->lastname = $obj->lastname;
						$tmpuser->firstname = $obj->firstname;
						$tmpuser->login = $obj->login;
						$tmpuser->photo = $obj->photo;
						$tmpuser->statut = $obj->statut;
						$userlink = $tmpuser->getNomUrl(1);
					}

					$countlabel = $langs->trans('LmdbCrmSignedQuotesPodiumCount', $obj->qty);

					$this->info_box_contents[] = array(
						0 => array(
							'td' => 'class=\"left\"',
							'asis' => 1,
							'text' => '<strong>#'.$rank.'</strong> '.$userlink,
						),
						1 => array(
							'td' => 'class=\"right\"',
							'text' => $countlabel,
						),
					);

					$rank++;
				}
			} else {
				$this->info_box_contents[] = array(
					0 => array(
						'td' => 'class=\"center\"',
						'text' => $langs->trans('LmdbCrmSignedQuotesPodiumEmpty'),
					),
				);
			}

			$this->db->free($resql);
		} else {
			$this->info_box_contents[] = array(
				0 => array(
					'td' => 'class=\"center\"',
					'asis' => 1,
					'text' => $this->db->lasterror(),
				),
			);
		}
	}
}
