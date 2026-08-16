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
 * \file    htdocs/custom/chinadiv/class/chinadivdivision.class.php
 * \ingroup chinadiv
 * \brief   DAO for llx_chinadiv_division.
 */

/**
 * China administrative division
 */
class ChinaDivDivision
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	public $id;
	public $code;
	public $level;
	public $parent_code;
	public $name;
	public $active;

	const LEVEL_PROVINCE = 1;
	const LEVEL_CITY = 2;
	const LEVEL_DISTRICT = 3;

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
	 * Get direct children of a division (or all provinces when parent is empty).
	 *
	 * @param	string	$parentCode	6-digit parent code ('' = provinces)
	 * @param	bool	$onlyActive	Exclude inactive rows
	 * @return	array<int,array{code:string,name:string,level:int}> or empty array on error
	 */
	public function getChildren($parentCode = '', $onlyActive = true)
	{
		$parentCode = trim((string) $parentCode);
		if ($parentCode !== '' && !preg_match('/^[0-9]{6}$/', $parentCode)) {
			return array();
		}

		$sql = "SELECT code, name, level FROM ".$this->db->prefix()."chinadiv_division";
		if ($parentCode === '') {
			$sql .= " WHERE level = ".self::LEVEL_PROVINCE;
		} else {
			$sql .= " WHERE parent_code = '".$this->db->escape($parentCode)."'";
		}
		if ($onlyActive) {
			$sql .= " AND active = 1";
		}
		$sql .= " ORDER BY code ASC";

		$result = array();
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $result;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$result[] = array('code' => $obj->code, 'name' => $obj->name, 'level' => (int) $obj->level);
		}
		return $result;
	}

	/**
	 * Search divisions by exact or leading name match.
	 *
	 * @param	string	$name		Division name (full or leading, UTF-8)
	 * @param	int		$level		Optional level filter (0 = all)
	 * @param	int		$limit		Max results
	 * @return	array<int,array{code:string,name:string,level:int,parent_code:string}>
	 */
	public function searchByName($name, $level = 0, $limit = 50)
	{
		$name = trim((string) $name);
		$result = array();
		if ($name === '' || mb_strlen($name) < 2) {
			return $result;
		}
		$sql = "SELECT code, name, level, parent_code FROM ".$this->db->prefix()."chinadiv_division";
		$sql .= " WHERE name LIKE '".$this->db->escape($name)."%'";
		if ($level > 0) {
			$sql .= " AND level = ".((int) $level);
		}
		$sql .= " AND active = 1";
		$sql .= " ORDER BY code ASC LIMIT ".max(1, min(500, (int) $limit));

		$resql = $this->db->query($sql);
		if (!$resql) {
			return $result;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$result[] = array('code' => $obj->code, 'name' => $obj->name, 'level' => (int) $obj->level, 'parent_code' => $obj->parent_code);
		}
		return $result;
	}

	/**
	 * Fetch one division by code.
	 *
	 * @param	string	$code	6-digit code
	 * @return	int		1 found, 0 not found, <0 error
	 */
	public function fetchByCode($code)
	{
		if (!preg_match('/^[0-9]{6}$/', (string) $code)) {
			return -1;
		}
		$sql = "SELECT rowid, code, level, parent_code, name, active FROM ".$this->db->prefix()."chinadiv_division";
		$sql .= " WHERE code = '".$this->db->escape($code)."'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return 0;
		}
		$this->id = $obj->rowid;
		$this->code = $obj->code;
		$this->level = (int) $obj->level;
		$this->parent_code = $obj->parent_code;
		$this->name = $obj->name;
		$this->active = $obj->active;
		return 1;
	}

	/**
	 * Import/refresh one row (idempotent). Used by the seed generator and the admin import.
	 *
	 * @param	string	$code		6-digit code
	 * @param	int		$level		1/2/3
	 * @param	string	$parentCode	Parent code or ''
	 * @param	string	$name		Division name
	 * @return	int					1 ok, <0 error
	 */
	public function upsert($code, $level, $parentCode, $name)
	{
		if (!preg_match('/^[0-9]{6}$/', (string) $code) || !in_array($level, array(1, 2, 3)) || trim((string) $name) === '') {
			return -1;
		}
		$sql = "INSERT INTO ".$this->db->prefix()."chinadiv_division (code, level, parent_code, name, active, date_creation)";
		$sql .= " VALUES ('".$this->db->escape($code)."', ".((int) $level).", ";
		$sql .= ($parentCode !== '' ? "'".$this->db->escape($parentCode)."'" : 'NULL');
		$sql .= ", '".$this->db->escape(dol_trunc(trim($name), 64))."', 1, '".$this->db->idate(dol_now())."')";
		$sql .= " ON DUPLICATE KEY UPDATE name = VALUES(name), level = VALUES(level), parent_code = VALUES(parent_code), active = 1";
		$resql = $this->db->query($sql);
		return $resql ? 1 : -1;
	}

	/**
	 * Count rows per level.
	 *
	 * @return	array<int,int>	level => count
	 */
	public function countByLevel()
	{
		$result = array(1 => 0, 2 => 0, 3 => 0);
		$sql = "SELECT level, COUNT(*) AS nb FROM ".$this->db->prefix()."chinadiv_division GROUP BY level";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $result;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$result[(int) $obj->level] = (int) $obj->nb;
		}
		return $result;
	}

	/**
	 * Save the division codes of a thirdparty (upsert, one row per thirdparty).
	 *
	 * @param	int		$fkSoc			Thirdparty id
	 * @param	string	$provinceCode	6-digit code or '' to clear
	 * @param	string	$cityCode		6-digit code or ''
	 * @param	string	$districtCode	6-digit code or ''
	 * @return	int							1 ok, <0 error
	 */
	public function upsertSocCodes($fkSoc, $provinceCode, $cityCode, $districtCode)
	{
		if ((int) $fkSoc <= 0) {
			return -1;
		}
		$valid = function ($c) { return preg_match('/^[0-9]{6}$/', (string) $c) ? $c : ''; };
		$p = $valid($provinceCode);
		$c = $valid($cityCode);
		$d = $valid($districtCode);
		if ($p === '' && $c === '' && $d === '') {
			// Nothing selected: remove any existing row
			$sql = "DELETE FROM ".$this->db->prefix()."chinadiv_soc_division WHERE fk_soc = ".((int) $fkSoc);
			return $this->db->query($sql) ? 1 : -1;
		}
		$sql = "INSERT INTO ".$this->db->prefix()."chinadiv_soc_division (fk_soc, province_code, city_code, district_code)";
		$sql .= " VALUES (".((int) $fkSoc).", ".($p !== '' ? "'".$p."'" : 'NULL').", ".($c !== '' ? "'".$c."'" : 'NULL').", ".($d !== '' ? "'".$d."'" : 'NULL').")";
		$sql .= " ON DUPLICATE KEY UPDATE province_code = VALUES(province_code), city_code = VALUES(city_code), district_code = VALUES(district_code)";
		$resql = $this->db->query($sql);
		return $resql ? 1 : -1;
	}

	/**
	 * Get the division codes stored for a thirdparty.
	 *
	 * @param	int		$fkSoc	Thirdparty id
	 * @return	array{province_code:string,city_code:string,district_code:string}|null	null when no row
	 */
	public function getSocCodes($fkSoc)
	{
		$sql = "SELECT province_code, city_code, district_code FROM ".$this->db->prefix()."chinadiv_soc_division";
		$sql .= " WHERE fk_soc = ".((int) $fkSoc);
		$resql = $this->db->query($sql);
		if (!$resql) {
			return null;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return null;
		}
		return array(
			'province_code' => (string) $obj->province_code,
			'city_code' => (string) $obj->city_code,
			'district_code' => (string) $obj->district_code,
		);
	}

	/**
	 * Import the standard pca-code.json dataset (modood/Administrative-divisions-of-China).
	 *
	 * @param	string	$json	Raw JSON content
	 * @return	array{inserted:int,failed:int} or array{error:string}
	 */
	public function importPcaJson($json)
	{
		$data = json_decode((string) $json, true);
		if (!is_array($data) || empty($data)) {
			return array('error' => 'invalid or empty JSON');
		}
		$stats = array('inserted' => 0, 'failed' => 0);
		foreach ($data as $prov) {
			$pcode = substr(str_pad((string) $prov['code'], 6, '0'), 0, 6);
			$rc = $this->upsert($pcode, 1, '', isset($prov['name']) ? $prov['name'] : '');
			$rc > 0 ? $stats['inserted']++ : $stats['failed']++;
			foreach (isset($prov['children']) ? $prov['children'] : array() as $city) {
				$ccode = substr(str_pad((string) $city['code'], 6, '0'), 0, 6);
				$rc = $this->upsert($ccode, 2, $pcode, isset($city['name']) ? $city['name'] : '');
				$rc > 0 ? $stats['inserted']++ : $stats['failed']++;
				foreach (isset($city['children']) ? $city['children'] : array() as $dist) {
					$dcode = substr(str_pad((string) $dist['code'], 6, '0'), 0, 6);
					$rc = $this->upsert($dcode, 3, $ccode, isset($dist['name']) ? $dist['name'] : '');
					$rc > 0 ? $stats['inserted']++ : $stats['failed']++;
				}
			}
		}
		return $stats;
	}
}
