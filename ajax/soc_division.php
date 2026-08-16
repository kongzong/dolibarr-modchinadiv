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
 * \file    htdocs/custom/chinadiv/ajax/soc_division.php
 * \ingroup chinadiv
 * \brief   AJAX endpoint: stored division codes of a thirdparty (edit-form prefill).
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

dol_include_once('/chinadiv/class/chinadivdivision.class.php');

/**
 * @var DoliDB $db
 * @var User $user
 */

if (empty($user->id) || !$user->hasRight('societe', 'lire') || !$user->hasRight('chinadiv', 'read')) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

header('Content-Type: application/json; charset=utf-8');

$fkSoc = (int) GETPOST('fk_soc', 'int');
if ($fkSoc <= 0) {
	header('HTTP/1.1 400 Bad Request');
	exit;
}

$dao = new ChinaDivDivision($db);
$codes = $dao->getSocCodes($fkSoc);

echo json_encode($codes ? $codes : new stdClass());
$db->close();
