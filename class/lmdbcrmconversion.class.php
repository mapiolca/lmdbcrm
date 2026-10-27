<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/** Optional source contract. Functional permissions stay in the entry points. */
final class LmdbCrmConversion
{
	/** @return array{code:string,label:string,available:bool,reason:string} */
	public static function source(): array
	{
		if (!isModEnabled('lmdblead')) {
			return array('code' => 'proposal', 'label' => 'LmdbCrmConversionSourceProposal',
				'available' => isModEnabled('propal'), 'reason' => isModEnabled('propal') ? '' : 'LmdbCrmConversionUnavailable');
		}
		dol_include_once('/lmdblead/class/lmdbleadcompatibility.class.php');
		dol_include_once('/lmdblead/class/lmdblead_lead.class.php');
		$contract = class_exists('LmdbLeadCompatibility') && class_exists('LmdbLead_Lead')
			&& (new ReflectionClass('LmdbLead_Lead'))->hasMethod('conversionStatisticsSql');
		$available = $contract && LmdbLeadCompatibility::isFeatureAvailable('conversion_statistics');
		return array('code' => 'lead', 'label' => 'LmdbCrmConversionSourceLead', 'available' => $available,
			'reason' => !$contract ? 'LmdbCrmConversionLeadUpdateRequired' : ($available ? '' : 'LmdbCrmConversionUnavailable'));
	}
}
