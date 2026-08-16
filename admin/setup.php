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

// Stats
$dao = new ChinaDivDivision($db);
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
}

llxFooter();
$db->close();
