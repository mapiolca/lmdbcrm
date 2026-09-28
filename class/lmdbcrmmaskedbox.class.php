<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Synthetic sections only; personal widgets use the uncached LmdbCrmBox renderer.
 */
class LmdbCrmMaskedBox extends ModeleBoxes
{
	/**
	 * Render fixed decorative shapes, never a sample or summary of real records.
	 *
	 * @param string $layout ranking, podium, orders or graph
	 * @return string
	 */
	public static function renderPlaceholder($layout)
	{
		global $langs;

		$html = '<div class="lmdbcrm-masked-preview">';
		$html .= '<p class="opacitymedium center">'.dol_escape_htmltag($langs->trans('LmdbCrmDataMasked')).'</p>';
		if ($layout === 'graph') {
			$html .= '<div class="lmdbcrm-masked-chart" aria-hidden="true">';
			for ($i = 0; $i < 6; $i++) {
				$html .= '<span class="lmdbcrm-masked-bar"></span>';
			}
			$html .= '</div>';
		} else {
			$columns = array(
				'ranking' => array('LmdbCrmSalesRep', 'LmdbCrmProposalsCount', 'LmdbCrmSignedProposalsCount', 'LmdbCrmQuotedAmount', 'LmdbCrmSignedAmount', 'LmdbCrmConversionRate'),
				'podium' => array('LmdbCrmSignedQuotesPodiumRank', 'LmdbCrmSalesRep', 'LmdbCrmMaskedResult'),
				'orders' => array('Ref', 'ThirdParty', 'AmountHT', 'DateModification', 'Status'),
			);
			$labels = isset($columns[$layout]) ? $columns[$layout] : $columns['ranking'];
			$html .= '<div class="div-table-responsive-no-min"><table class="noborder centpercent"><tr class="liste_titre">';
			foreach ($labels as $label) {
				$html .= '<th>'.dol_escape_htmltag($langs->trans($label)).'</th>';
			}
			$html .= '</tr>';
			$rows = $layout === 'podium' ? 3 : 5;
			for ($row = 0; $row < $rows; $row++) {
				$html .= '<tr class="oddeven" aria-hidden="true">';
				foreach ($labels as $label) {
					$html .= '<td><span class="lmdbcrm-masked-value"></span></td>';
				}
				$html .= '</tr>';
			}
			$html .= '</table></div>';
		}
		return $html.'</div>';
	}
}
