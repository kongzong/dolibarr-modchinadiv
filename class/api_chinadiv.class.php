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

use Luracast\Restler\RestException;

/**
 * \file    htdocs/custom/chinadiv/class/api_chinadiv.class.php
 * \ingroup chinadiv
 * \brief   REST API for Chinese administrative divisions.
 */

dol_include_once('/chinadiv/class/chinadivdivision.class.php');

/**
 * API class for ChinaDiv module
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Chinadiv extends DolibarrApi
{
	/**
	 * @var DoliDB $db Database object
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @url GET /
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * List divisions: children of a parent code, or search by name.
	 *
	 * @url	GET divisions
	 *
	 * @param	string	$parent	6-digit parent code ('' or absent = all provinces)
	 * @param	string	$q		Search by leading name (ignored when parent is set)
	 * @param	int		$level	Optional level filter for name search (1/2/3)
	 * @return	array<int,array<string,mixed>>
	 * @throws RestException 403 Not allowed
	 */
	public function getDivisions($parent = '', $q = '', $level = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('chinadiv', 'read')) {
			throw new RestException(403);
		}
		$dao = new ChinaDivDivision($this->db);
		if ((string) $parent !== '') {
			return $dao->getChildren((string) $parent);
		}
		if ((string) $q !== '') {
			return $dao->searchByName((string) $q, (int) $level);
		}
		return $dao->getChildren('');
	}

	/**
	 * Get the division codes stored for a thirdparty (machine-readable).
	 *
	 * @url	GET divisions/soc/{socid}
	 *
	 * @param	int		$socid	Thirdparty id
	 * @return	array<string,string>|null
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getSocDivision($socid)
	{
		if (!DolibarrApiAccess::$user->hasRight('chinadiv', 'read') || !DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}
		$dao = new ChinaDivDivision($this->db);
		$codes = $dao->getSocCodes((int) $socid);
		if (is_null($codes)) {
			throw new RestException(404, 'No division codes stored for thirdparty '.$socid);
		}
		return $codes;
	}

	/**
	 * Get the division codes stored for a contact (machine-readable, V0.4).
	 *
	 * @url	GET divisions/contact/{contactid}
	 *
	 * @param	int		$contactid	Contact id
	 * @return	array<string,string>|null
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getContactDivision($contactid)
	{
		if (!DolibarrApiAccess::$user->hasRight('chinadiv', 'read') || !DolibarrApiAccess::$user->hasRight('societe', 'contact', 'lire')) {
			throw new RestException(403);
		}
		$dao = new ChinaDivDivision($this->db);
		$codes = $dao->getContactCodes((int) $contactid);
		if (is_null($codes)) {
			throw new RestException(404, 'No division codes stored for contact '.$contactid);
		}
		return $codes;
	}
}
