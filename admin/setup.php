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
 * \file    htdocs/custom/chinadiv/admin/setup.php
 * \ingroup chinadiv
 * \brief   ChinaDiv setup page: data stats and import tool.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
dol_include_once('/chinadiv/class/chinadivdivision.class.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "chinadiv@chinadiv"));

if (!$user->hasRight('chinadiv', 'read')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

llxHeader('', $langs->trans("ChinaDivSetup"));

print load_fiche_titre($langs->trans("ChinaDivSetup"), '', 'chinadiv@chinadiv');

$dao = new ChinaDivDivision($db);

// Import action
if ($action == 'import' && $user->hasRight('chinadiv', 'admin')) {
	if (!empty($_FILES['pcafile']['tmp_name']) && is_uploaded_file($_FILES['pcafile']['tmp_name'])) {
		$json = file_get_contents($_FILES['pcafile']['tmp_name']);
		$dao = new ChinaDivDivision($db);
		$result = $dao->importPcaJson($json);
		if (isset($result['error'])) {
			setEventMessages($langs->trans("ChinaDivImportFailed").': '.$result['error'], null, 'errors');
		} else {
			setEventMessages($langs->trans("ChinaDivImportOk", $result['inserted'], $result['failed']), null, 'mesgs');
		}
	} else {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("File")), null, 'errors');
	}
	$action = '';
}

// Address normalization (V0.4): store codes parsed from backlog addresses
if ($action == 'normalize_apply' && $user->hasRight('chinadiv', 'admin')) {
	$pending = $dao->getSocsMissingCodes(500);
	$applied = 0;
	$unparsed = 0;
	foreach ($pending as $soc) {
		$parsed = $dao->parseAddress($soc['address']);
		if ($parsed !== null) {
			$rc = $dao->upsertSocCodes($soc['id'], $parsed['province_code'], $parsed['city_code'], $parsed['district_code']);
			if ($rc > 0) {
				$applied++;
			} else {
				$unparsed++;
			}
		} else {
			$unparsed++;
		}
	}
	setEventMessages($langs->trans("ChinaDivNormalizeOk", $applied, $unparsed), null, 'mesgs');
	$action = '';
}

// Stats
$counts = $dao->countByLevel();

print '<table class="border centpercent">';
print '<tr><td class="fieldtitle">'.$langs->trans("ChinaDivProvinces").'</td><td>'.(int) $counts[1].'</td></tr>';
print '<tr><td class="fieldtitle">'.$langs->trans("ChinaDivCities").'</td><td>'.(int) $counts[2].'</td></tr>';
print '<tr><td class="fieldtitle">'.$langs->trans("ChinaDivDistricts").'</td><td>'.(int) $counts[3].'</td></tr>';
print '</table>';

// Import form (refresh data, e.g. after an annual stats-bureau code update)
if ($user->hasRight('chinadiv', 'admin')) {
	print '<br>';
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="import">';
	print '<table class="border centpercent">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ChinaDivImportTitle").'</td></tr>';
	print '<tr><td class="titlefield">'.$langs->trans("File").' (pca-code.json)</td><td><input type="file" name="pcafile" accept=".json,application/json" required></td></tr>';
	print '<tr><td></td><td><input class="button" type="submit" value="'.$langs->trans("Import").'"></td></tr>';
	print '</table>';
	print '<br><span class="opacitymedium">'.$langs->trans("ChinaDivImportHelp").'</span>';
	print '</form>';

	// Backlog address normalization (preview + apply)
	print '<br>';
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="normalize_apply">';
	print '<table class="border centpercent">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("ChinaDivNormalizeTitle").'</td></tr>';
	$pending = $dao->getSocsMissingCodes(500);
	if (empty($pending)) {
		print '<tr><td colspan="3"><span class="opacitymedium">'.$langs->trans("ChinaDivNormalizeNone").'</span></td></tr>';
	} else {
		print '<tr><td colspan="3"><span class="opacitymedium">'.$langs->trans("ChinaDivNormalizeBacklog", count($pending)).'</span></td></tr>';
		print '<tr class="liste_titre"><td>'.$langs->trans("ThirdParty").'</td><td>'.$langs->trans("Address").'</td><td>'.$langs->trans("ChinaDivNormalizeRegionCol").'</td></tr>';
		$shown = 0;
		foreach ($pending as $soc) {
			if ($shown >= 50) { break; }
			$shown++;
			$parsed = $dao->parseAddress($soc['address']);
			if ($parsed !== null) {
				$provName = $cityName = $distName = '';
				if ($dao->fetchByCode($parsed['province_code']) > 0) { $provName = $dao->name; }
				if ($parsed['city_code'] !== '' && $dao->fetchByCode($parsed['city_code']) > 0) { $cityName = $dao->name; }
				if ($parsed['district_code'] !== '' && $dao->fetchByCode($parsed['district_code']) > 0) { $distName = $dao->name; }
				$parsedText = dol_escape_htmltag($provName.' / '.$cityName.' / '.$distName);
			} else {
				$parsedText = '<span class="error">'.$langs->trans("ChinaDivNormalizeUnparsed").'</span>';
			}
			print '<tr><td>'.dol_escape_htmltag($soc['name']).'</td><td>'.dol_escape_htmltag($soc['address']).'</td><td>'.$parsedText.'</td></tr>';
		}
		print '<tr><td colspan="3"><input class="button" type="submit" value="'.$langs->trans("ChinaDivNormalizeApply").'"';
		print ' onclick="return confirm(\''.dol_escape_js($langs->trans("ChinaDivNormalizeApply")).'?\');">';
		print '</td></tr>';
	}
	print '</table>';
	print '<br><span class="opacitymedium">'.$langs->trans("ChinaDivNormalizeHelp").'</span>';
	print '</form>';
}

llxFooter();
$db->close();
