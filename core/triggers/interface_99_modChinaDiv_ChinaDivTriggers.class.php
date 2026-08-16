<?php
/* Copyright (C) 2026  modChinaDiv contributors
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/custom/chinadiv/core/triggers/interface_99_modChinaDiv_ChinaDivTriggers.class.php
 * \ingroup chinadiv
 * \brief   Store the division codes submitted by the cascade selector
 *          (hidden inputs chinadiv_province_code / city / district) when a
 *          thirdparty is created or modified. Failures are logged, never fatal.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 * Class InterfaceChinaDivTriggers
 */
class InterfaceChinaDivTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "base";
		$this->description = "Triggers of the ChinaDiv module";
		$this->version = '0.3.0';
		$this->picto = 'generic';
	}

	/**
	 * Function called when a Dolibarr business event occurs.
	 *
	 * @param	string		$action		Event action label
	 * @param	CommonObject	$object		Object
	 * @param	User		$user		User
	 * @param	Translate	$langs		Lang object
	 * @param	Conf		$conf		Config
	 * @return	int						0
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if ($action !== 'COMPANY_CREATE' && $action !== 'COMPANY_MODIFY') {
			return 0;
		}
		if (empty($conf->chinadiv->enabled) || empty($object->id)) {
			return 0;
		}
		// Cascade selector only posts these on the thirdparty form
		if (!isset($_POST['chinadiv_province_code']) && !isset($_POST['chinadiv_city_code'])) {
			return 0;
		}

		dol_include_once('/chinadiv/class/chinadivdivision.class.php');
		$dao = new ChinaDivDivision($this->db);
		$result = $dao->upsertSocCodes(
			(int) $object->id,
			isset($_POST['chinadiv_province_code']) ? GETPOST('chinadiv_province_code', 'aZ09') : '',
			isset($_POST['chinadiv_city_code']) ? GETPOST('chinadiv_city_code', 'aZ09') : '',
			isset($_POST['chinadiv_district_code']) ? GETPOST('chinadiv_district_code', 'aZ09') : ''
		);
		if ($result < 0) {
			// Never block the business action because of the mapping storage
			dol_syslog('ChinaDiv trigger: upsertSocCodes failed for soc '.$object->id.' err='.$this->db->lasterror(), LOG_WARNING);
		}
		return 0;
	}
}
