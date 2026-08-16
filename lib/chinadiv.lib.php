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
 * \file    htdocs/custom/chinadiv/lib/chinadiv.lib.php
 * \ingroup chinadiv
 * \brief   Helper functions for other modules consuming division data.
 */

dol_include_once('/chinadiv/class/chinadivdivision.class.php');

/**
 * Get divisions (children of a parent code, provinces by default).
 *
 * @param	string	$parentCode	6-digit parent code ('' = provinces)
 * @return	array<int,array{code:string,name:string,level:int}>
 */
function chinadiv_get_divisions($parentCode = '')
{
	global $db;
	$dao = new ChinaDivDivision($db);
	return $dao->getChildren($parentCode);
}

/**
 * Search divisions by leading name.
 *
 * @param	string	$name	Name (at least 2 UTF-8 chars)
 * @param	int		$level	Optional level filter
 * @return	array<int,array{code:string,name:string,level:int,parent_code:string}>
 */
function chinadiv_find_by_name($name, $level = 0)
{
	global $db;
	$dao = new ChinaDivDivision($db);
	return $dao->searchByName($name, $level);
}

/**
 * Build a Chinese-style single line address: 省 市 区 详细地址.
 * "市辖区" level-2 entries are dropped for a natural result.
 *
 * @param	string	$province	Province name or code
 * @param	string	$city		City name or code ('' = auto from district)
 * @param	string	$district	District name or code
 * @param	string	$detail		Street/detail part
 * @return	string
 */
function chinadiv_format_address($province = '', $city = '', $district = '', $detail = '')
{
	global $db;
	$dao = new ChinaDivDivision($db);

	$parts = array();
	foreach (array('province' => $province, 'city' => $city, 'district' => $district) as $key => $value) {
		$value = trim((string) $value);
		if ($value === '') {
			continue;
		}
		if (preg_match('/^[0-9]{6}$/', $value)) {
			if ($dao->fetchByCode($value) > 0) {
				$value = $dao->name;
			}
		}
		if ($value !== '' && $value !== '市辖区' && $value !== '县') {
			$parts[] = $value;
		}
	}
	if (trim((string) $detail) !== '') {
		$parts[] = trim((string) $detail);
	}
	return implode(' ', $parts);
}
