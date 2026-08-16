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
 * \file    htdocs/custom/chinadiv/tests/unit/ChinaDivTest.php
 * \ingroup chinadiv
 * \brief   modChinaDiv structural tests (no DB needed).
 */

use PHPUnit\Framework\TestCase;

require_once dirname(dirname(__DIR__)).'/class/chinadivdivision.class.php';

/**
 * Class ChinaDivTest
 */
class ChinaDivTest extends TestCase
{
	/**
	 * Descriptor must declare table loading, permissions and no menu pollution.
	 */
	public function testDescriptor()
	{
		$content = file_get_contents(__DIR__.'/../../core/modules/modChinaDiv.class.php');
		$this->assertStringContainsString("_load_tables('/chinadiv/sql/')", $content);
		$this->assertStringContainsString("rights_class = 'chinadiv'", $content);
		foreach (array("'read'", "'admin'") as $perm) {
			$this->assertStringContainsString($perm, $content);
		}
		$this->assertStringContainsString('menu = array();', $content, 'utility module must not add menus');
	}

	/**
	 * Seed SQL must contain the full dataset and the unique code index.
	 */
	public function testSeedSql()
	{
		$content = file_get_contents(__DIR__.'/../../sql/llx_chinadiv_division.sql');
		$this->assertStringContainsString('uk_chinadiv_code', $content, 'unique index for idempotency');
		$this->assertStringContainsString('(110000, 1, NULL, \'北京市\')', $content);
		$this->assertStringContainsString("(440100, 2, 440000, '广州市')", $content);
		$this->assertStringContainsString('(810000, 1, NULL, \'香港特别行政区\')', $content);
		$rowCount = substr_count($content, "\n(") + substr_count($content, ',(');
		$this->assertGreaterThan(3300, $rowCount, 'full dataset expected (3300+ rows, 直筒子市 towns excluded)');
	}

	/**
	 * DAO surface.
	 */
	public function testDaoSurface()
	{
		foreach (array('getChildren', 'searchByName', 'fetchByCode', 'upsert', 'countByLevel', 'importPcaJson') as $method) {
			$this->assertTrue(method_exists('ChinaDivDivision', $method), 'ChinaDivDivision::'.$method.' must exist');
		}
	}

	/**
	 * Input validation: bad codes must be rejected before any SQL.
	 */
	public function testCodeValidation()
	{
		$dao = new ChinaDivDivision(null);
		$this->assertSame(array(), $dao->getChildren('abc123'));
		$this->assertSame(-1, $dao->fetchByCode('12'));
		$this->assertSame(-1, $dao->upsert('bad', 1, '', 'x'));
	}

	/**
	 * API must check the read permission.
	 */
	public function testApiPermissionCheck()
	{
		$content = file_get_contents(__DIR__.'/../../class/api_chinadiv.class.php');
		$this->assertStringContainsString("hasRight('chinadiv', 'read')", $content);
	}

	/**
	 * V0.3 structured storage: table, DAO methods, trigger, JS hidden fields.
	 */
	public function testSocDivisionStorage()
	{
		$this->assertStringContainsString('uk_chinadiv_soc', file_get_contents(__DIR__.'/../../sql/llx_chinadiv_soc_division.sql'));
		foreach (array('upsertSocCodes', 'getSocCodes') as $method) {
			$this->assertTrue(method_exists('ChinaDivDivision', $method), 'ChinaDivDivision::'.$method.' must exist');
		}
		$trigger = file_get_contents(__DIR__.'/../../core/triggers/interface_99_modChinaDiv_ChinaDivTriggers.class.php');
		$this->assertStringContainsString('COMPANY_CREATE', $trigger);
		$this->assertStringContainsString('COMPANY_MODIFY', $trigger);
		$this->assertStringContainsString('return 0;', $trigger, 'trigger must never block business flow');
		$desc = file_get_contents(__DIR__.'/../../core/modules/modChinaDiv.class.php');
		$this->assertStringContainsString("'triggers' => 1", $desc);
		$js = file_get_contents(__DIR__.'/../../js/chinadiv.js.php');
		$this->assertStringContainsString('chinadiv_province_code', $js, 'JS must post machine-readable codes');
		$this->assertStringContainsString('prefillStored', $js, 'edit form must prefill stored codes');
		$api = file_get_contents(__DIR__.'/../../class/api_chinadiv.class.php');
		$this->assertStringContainsString('divisions/soc/{socid}', $api);
	}

	/**
	 * V0.2 cascade: JS declared in module_parts, AJAX endpoint checks permission,
	 * JS derives base URL without server env.
	 */
	public function testCascadeSelector()
	{
		$desc = file_get_contents(__DIR__.'/../../core/modules/modChinaDiv.class.php');
		$this->assertStringContainsString("'/chinadiv/js/chinadiv.js.php'", $desc);
		$ajax = file_get_contents(__DIR__.'/../../ajax/divisions.php');
		$this->assertStringContainsString("hasRight('chinadiv', 'read')", $ajax, 'AJAX endpoint must check permission');
		$js = file_get_contents(__DIR__.'/../../js/chinadiv.js.php');
		$this->assertStringContainsString('document.currentScript', $js, 'JS must derive base URL from its own script tag');
		$this->assertStringContainsString('chinadiv-cascade', $js);
	}
}
