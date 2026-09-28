<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
require_once __DIR__.'/lmdbcrmmaskedbox.class.php';

/**
 * Render freshly authorised data with the native UI, without reusing stale scopes.
 * Permission checks remain direct calls in each concrete widget.
 */
class LmdbCrmBox extends ModeleBoxes
{
	/** @var bool Whether loadBox has reached the authorised business-data branch. */
	protected $lmdbcrmDataLoaded = false;

	/** @var bool Full-read scope used by the last successful load. */
	protected $lmdbcrmLoadedAll = false;

	/**
	 * ModeleBoxes v20+ caches HTML by user/entity but not by permissions or filters.
	 * Invalidate only this widget's old cache and suppress cache writes during the
	 * native render. Restore the exact configuration object even after an exception.
	 *
	 * @param array<string, mixed>|null $head Native header
	 * @param array<int, array<int, array<string, mixed>>>|null $contents Native rows
	 * @param int $nooutput Return HTML instead of printing it
	 * @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		global $conf, $user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		// Match native concatenation exactly: an internal user's socid may be null.
		$boxid = (string) $this->box_id;
		$socid = (string) $user->socid;
		if (!preg_match('/^[0-9]*$/D', $boxid) || !preg_match('/^[0-9]*$/D', $socid)) {
			return '';
		}
		$cachefile = DOL_DATA_ROOT.'/users/temp/widgets/box-'.get_class($this).'id-'.$boxid
			.'-e'.((int) $conf->entity).'-u'.((int) $user->id).'-s'.$socid.'.cache';
		if (file_exists($cachefile) && dol_delete_file($cachefile, 1, 1, 1, null, false, 0) <= 0) {
			dol_syslog(__METHOD__.' Cannot invalidate widget cache; refusing stale output', LOG_ERR);
			return '';
		}

		$originalGlobal = $conf->global;
		$conf->global = clone $originalGlobal;
		$conf->global->MAIN_ACTIVATE_FILECACHE = 0;
		try {
			return parent::showBox($head, $contents, $nooutput);
		} finally {
			$conf->global = $originalGlobal;
		}
	}
}
