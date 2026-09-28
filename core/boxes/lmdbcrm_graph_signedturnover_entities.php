<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
require_once __DIR__.'/lmdbcrm_graph_signedturnover.php';

/** Monthly signed turnover per shared entity, on one common fiscal period. */
class lmdbcrm_graph_signedturnover_entities extends lmdbcrm_graph_signedturnover
{
	public $boxcode = 'lmdbcrmsignedturnoverentities';
	public $boxlabel = 'LmdbCrmSignedTurnoverEntitiesTitle';
	public $depends = array('lmdbcrm', 'propal', 'multicompany');

	/** @var string Entity scope used when loading, checked again before rendering. */
	private $loadedEntities = '';

	/**
	 * @param DoliDB $db Database handler
	 * @param string $param Box parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		parent::__construct($db, $param);
		$this->hidden = $this->hidden || !isModEnabled('multicompany')
			|| count(array_unique(explode(',', getEntity('propal')))) < 2;
	}

	/**
	 * @param int<0,max> $max Maximum records (unused for monthly aggregates)
	 * @return void
	 */
	public function loadBox($max = 1)
	{
		global $langs, $conf, $user;
		$this->info_box_head = array();
		$this->info_box_contents = array();
		$this->lmdbcrmDataLoaded = false;
		$this->loadedEntities = '';
		$this->hidden = !isModEnabled('lmdbcrm') || !isModEnabled('propal') || !isModEnabled('multicompany')
			|| !empty($user->socid) || !$user->hasRight('propal', 'lire')
			|| (!$user->hasRight('lmdbcrm', 'widgets', 'readall') && !$user->hasRight('lmdbcrm', 'widgets', 'read'))
			|| count(array_unique(explode(',', getEntity('propal')))) < 2;
		if ($this->hidden) return;

		$langs->loadLangs(array('main', 'lmdbcrm@lmdbcrm'));
		$this->lmdbcrmLoadedAll = $user->hasRight('lmdbcrm', 'widgets', 'readall');
		$this->loadedEntities = getEntity('propal');
		$range = $this->getFiscalYearRange();
		// One LEFT JOIN keeps authorised entities with no signed proposal at zero.
		// Restrictions belong in the join, before any entity/month aggregation.
		$sql = "SELECT e.rowid as entity, e.label, YEAR(p.date_signature) as y, MONTH(p.date_signature) as m";
		$sql .= ", SUM(p.total_ht) as amount, COUNT(p.rowid) as qty FROM ".MAIN_DB_PREFIX."entity as e";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON p.entity = e.rowid";
		$sql .= " AND p.entity IN (".$this->db->sanitize($this->loadedEntities).")";
		$sql .= " AND p.fk_statut IN (".Propal::STATUS_SIGNED.",".Propal::STATUS_BILLED.")";
		$sql .= " AND p.date_signature >= '".$this->db->idate($range['start'])."'";
		$sql .= " AND p.date_signature <= '".$this->db->idate($range['end'])."'";
		if (!$user->hasRight('lmdbcrm', 'widgets', 'readall')) {
			$sql .= " AND p.fk_user_author = ".((int) $user->id);
		}
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $user->id).")";
		}
		$sql .= " WHERE e.rowid IN (".$this->db->sanitize($this->loadedEntities).")";
		$sql .= " GROUP BY e.rowid, e.label, YEAR(p.date_signature), MONTH(p.date_signature) ORDER BY e.rowid, y, m";
		$resql = $this->db->query($sql);
		$this->info_box_head = array(
			'text' => $langs->trans('LmdbCrmSignedTurnoverEntitiesTitle'),
			'limit' => 0,
			'subpicto' => 'help',
			'subtext' => dol_escape_htmltag($langs->transnoentitiesnoconv('LmdbCrmSignedTurnoverEntitiesTooltip')),
			'subclass' => 'classfortooltip',
		);
		$content = '';
		if (!$resql) {
			// An unavailable result is not an empty business period. No SQL in the UI.
			dol_syslog(__METHOD__.' Failed to aggregate signed turnover by entity', LOG_ERR);
			$content = '<span class="error">'.dol_escape_htmltag($langs->trans('Error')).'</span>';
		} else {
			$labels = array();
			$monthly = array();
			$records = 0;
			while (is_object($row = $this->db->fetch_object($resql))) {
				$id = (int) $row->entity;
				$labels[$id] = (string) $row->label;
				$records += (int) $row->qty;
				if ($row->y !== null && $row->m !== null) {
					$key = sprintf('%04d-%02d', (int) $row->y, (int) $row->m);
					$monthly[$id][$key] = (float) $row->amount;
				}
			}
			$this->db->free($resql);
			if ($records === 0) {
				$content = '<span class="opacitymedium">'.dol_escape_htmltag($langs->trans('NoRecordFound')).'</span>';
			} else {
				$data = array();
				foreach ($this->buildMonthSequence($range['start']) as $month) {
					$values = array($month['label']);
					foreach ($labels as $id => $label) $values[] = $monthly[$id][$month['key']] ?? 0.0;
					$data[] = $values;
				}
				$graph = new DolGraph();
				$graph->SetData($data);
				$graph->SetLegend(array_map('dol_escape_htmltag', array_values($labels)));
				// Stable colours for any number of entities (native default has only three).
				foreach (array_keys($labels) as $index => $id) {
					$graph->datacolor[$index] = '#'.substr(hash('sha256', 'lmdbcrm-entity-'.$id), 0, 6);
				}
				$graph->SetType(array('lines'));
				$graph->setWidth(!empty($conf->dol_optimize_smallscreen) ? '350' : '720');
				$graph->setHeight(!empty($conf->dol_optimize_smallscreen) ? '240' : '320');
				$graph->setShowLegend(1);
				$graph->draw('lmdbcrmsignedentities_e'.((int) $conf->entity).'_u'.((int) $user->id)
					.'_'.($this->lmdbcrmLoadedAll ? 'all' : 'own').'_'.$range['start'].'_'.substr(hash('sha256', $this->loadedEntities), 0, 12));
				$content = $graph->show(0);
			}
		}
		$content = '<p class="opacitymedium">'.dol_escape_htmltag($langs->trans('LmdbCrmSignedTurnoverCurveFiscalYear', $range['label'])).'</p>'.$content;
		if (!$user->hasRight('lmdbcrm', 'widgets', 'readall')) {
			$content = '<p class="opacitymedium">'.dol_escape_htmltag($langs->trans('LmdbCrmOwnDataOnly')).'</p>'.$content;
		}
		$this->info_box_contents = array(array(array('td' => 'class="center"', 'asis' => 1, 'text' => $content)));
		$this->lmdbcrmDataLoaded = true;
	}

	/** @inheritdoc */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		if (!isModEnabled('multicompany') || count(array_unique(explode(',', getEntity('propal')))) < 2
			|| $this->loadedEntities !== getEntity('propal')) {
			$this->info_box_head = array();
			$this->info_box_contents = array();
			$this->lmdbcrmDataLoaded = false;
			return '';
		}
		// Parent rechecks the native and CRM rights directly before rendering.
		return parent::showBox($head, $contents, $nooutput);
	}
}
