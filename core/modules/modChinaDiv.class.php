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
 *  \defgroup   chinadiv     Module ChinaDiv
 *  \brief      Chinese administrative divisions (province/city/district) module descriptor.
 *
 *  \file       htdocs/custom/chinadiv/core/modules/modChinaDiv.class.php
 *  \ingroup    chinadiv
 *  \brief      Description and activation file for module ChinaDiv
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module ChinaDiv
 */
class modChinaDiv extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique)
		$this->numero = 501560;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'chinadiv';

		$this->family = "base";
		$this->module_position = '91';

		$this->name = preg_replace('/^mod/i', '', get_class($this));

		$this->description = "Chinese administrative divisions data and tools";
		$this->descriptionlong = "Standard stats-bureau division codes (province/city/district), lookup library, import tool and REST API. Foundation module for other China-ecosystem modules.";

		$this->editor_name = 'modChinaDiv';
		$this->editor_url = 'https://example.com';

		$this->version = '0.1.0';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		$this->picto = 'generic';

		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array(),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0,
		);

		// No data directories needed
		$this->dirs = array();

		$this->config_page_url = array("setup.php@chinadiv");

		$this->hidden = getDolGlobalInt('MODULE_CHINADIV_DISABLED');
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();

		$this->langfiles = array("chinadiv@chinadiv");

		$this->phpmin = array(7, 1);
		$this->need_dolibarr_version = array(19, -3);
		$this->need_javascript_ajax = 0;

		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		$this->const = array();

		if (!isModEnabled("chinadiv")) {
			$conf->chinadiv = new stdClass();
			$conf->chinadiv->enabled = 0;
		}

		$this->tabs = array();

		$this->dictionaries = array();

		$this->boxes = array();

		$this->cronjobs = array();

		// Permissions
		$this->rights = array();
		$r = 0;

		$this->rights[$r][0] = $this->numero . 11;
		$this->rights[$r][1] = 'Read China divisions data';
		$this->rights[$r][4] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . 31;
		$this->rights[$r][1] = 'Administer China divisions (import)';
		$this->rights[$r][4] = 'admin';
		$r++;

		// Utility module: no menu entries, reachable from module setup page
		$this->menu = array();
	}

	/**
	 *  Function called when module is enabled.
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int<-1,1>          1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Create table and load the full dataset (3432 rows, stats-bureau codes)
		$result = $this->_load_tables('/chinadiv/sql/');
		if ($result < 0) {
			return -1;
		}

		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *
	 *	@param	string		$options	Options when enabling module ('', 'noboxes')
	 *	@return	int<-1,1>				1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
