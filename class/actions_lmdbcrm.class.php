<?php
/* Copyright (C) 2025		Pierre Ardoin			<developpeur@lesmetiersdubatiment.fr>
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
 * \file    lmdbcrm/class/actions_lmdbcrm.class.php
 * \ingroup lmdbcrm
 * \brief   LmdbCrm hooks.
 */

/**
 * Hooks class for lmdbcrm.
 */
class ActionsLmdbcrm
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Output HTML
	 */
	public $resprints = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Add more CSS files.
	 *
	 * @param array<string,mixed> $parameters Hook parameters
	 * @param object $object Current object
	 * @param string $action Current action
	 * @param HookManager $hookmanager Hook manager
	 * @return int
	 */
	public function addMoreCss($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		// EN: Load module graph responsive stylesheet when module is enabled.
		// FR: Charger la feuille de style responsive des graphiques quand le module est actif.
		if (empty($conf->lmdbcrm->enabled)) {
			return 0;
		}

		static $isLoaded = false;

		// EN: Avoid duplicate CSS injection.
		// FR: Éviter l'injection multiple du CSS.
		if (!$isLoaded) {
			$this->resprints .= '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/lmdbcrm/css/lmdbcrm_graph.css', 1).'">';
			$isLoaded = true;
		}

		return 0;
	}
}
