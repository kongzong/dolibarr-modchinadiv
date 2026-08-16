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
 * \file    htdocs/custom/chinadiv/js/chinadiv.js.php
 * \ingroup chinadiv
 * \brief   Cascade province/city/district selector injected on thirdparty forms.
 *
 * Loaded on every page (module_parts['js']); activates only on thirdparty
 * create/edit forms (identified by the town + state_id fields in the main
 * object form). The Dolibarr base URL is derived from this script's own
 * src attribute, so no server-side env is needed here.
 */

header('Content-Type: application/javascript; charset=utf-8');
?>
// ChinaDiv cascade selector (IIFE, no globals)
(function () {
	'use strict';

	// Derive the Dolibarr base URL from this script tag (/.../custom/chinadiv/js/chinadiv.js.php)
	function baseUrl() {
		var el = document.currentScript || (function () {
			var scripts = document.querySelectorAll('script[src*="/custom/chinadiv/js/chinadiv.js.php"]');
			return scripts.length ? scripts[scripts.length - 1] : null;
		})();
		if (!el) { return ''; }
		var m = el.src.match(/^(.*\/)custom\/chinadiv\/js\/chinadiv\.js\.php/);
		return m ? m[1] : '';
	}

	var ajaxUrl = baseUrl() + 'custom/chinadiv/ajax/divisions.php';
	var hint = <?php echo json_encode('中国行政区划快捷选择：选定后自动填充省份与城市字段 / Quick select: fills province and city fields'); ?>;

	function findTargetForm() {
		var forms = document.querySelectorAll('form');
		for (var i = 0; i < forms.length; i++) {
			var f = forms[i];
			if (f.querySelector('input[name="town"]') && f.querySelector('select[name="state_id"]')) {
				return f;
			}
		}
		return null;
	}

	function fetchDivisions(parent) {
		var url = ajaxUrl + '?parent=' + encodeURIComponent(parent);
		return fetch(url, {credentials: 'same-origin'}).then(function (r) {
			if (!r.ok) { throw new Error('http ' + r.status); }
			return r.json();
		});
	}

	function fillSelect(sel, items, placeholder) {
		sel.innerHTML = '';
		var opt = document.createElement('option');
		opt.value = '';
		opt.textContent = placeholder;
		sel.appendChild(opt);
		items.forEach(function (it) {
			var o = document.createElement('option');
			o.value = it.code;
			o.textContent = it.name;
			sel.appendChild(o);
		});
	}

	function setStateByProvinceName(form, provinceName) {
		var stateSel = form.querySelector('select[name="state_id"]');
		if (!stateSel) { return; }
		var options = stateSel.options;
		for (var i = 0; i < options.length; i++) {
			if (options[i].text === provinceName) {
				stateSel.selectedIndex = i;
				return;
			}
		}
	}

	function applyTown(form, cityName, districtName) {
		var town = form.querySelector('input[name="town"]');
		if (!town) { return; }
		var value = cityName || '';
		if (districtName && districtName !== '市辖区' && districtName !== '县') {
			value = (value ? value + ' ' : '') + districtName;
		}
		town.value = value;
	}

	function buildCascade() {
		var form = findTargetForm();
		if (!form || form.dataset.chinadivDone) { return; }
		form.dataset.chinadivDone = '1';

		var townField = form.querySelector('input[name="town"]');
		if (!townField) { return; }

		var container = document.createElement('div');
		container.className = 'chinadiv-cascade';
		container.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;margin:4px 0 10px 0;';

		var selProv = document.createElement('select');
		var selCity = document.createElement('select');
		var selDist = document.createElement('select');
		[selProv, selCity, selDist].forEach(function (s) { s.className = 'flat minwidth200'; });
		fillSelect(selCity, [], '— 市 —');
		fillSelect(selDist, [], '— 区/县 —');

		selProv.addEventListener('change', function () {
			fillSelect(selCity, [], '— 市 —');
			fillSelect(selDist, [], '— 区/县 —');
			if (!selProv.value) { return; }
			var provName = selProv.options[selProv.selectedIndex].text;
			setStateByProvinceName(form, provName);
			applyTown(form, '', '');
			fetchDivisions(selProv.value).then(function (items) {
				fillSelect(selCity, items, '— 市 —');
			}).catch(function () {});
		});

		selCity.addEventListener('change', function () {
			fillSelect(selDist, [], '— 区/县 —');
			if (!selCity.value) { return; }
			applyTown(form, selCity.options[selCity.selectedIndex].text, '');
			fetchDivisions(selCity.value).then(function (items) {
				fillSelect(selDist, items, '— 区/县 —');
			}).catch(function () {});
		});

		selDist.addEventListener('change', function () {
			if (!selDist.value) { return; }
			applyTown(
				form,
				selCity.value ? selCity.options[selCity.selectedIndex].text : '',
				selDist.options[selDist.selectedIndex].text
			);
		});

		container.appendChild(selProv);
		container.appendChild(selCity);
		container.appendChild(selDist);

		var label = document.createElement('div');
		label.style.cssText = 'width:100%;font-size:11px;color:#888;';
		label.textContent = hint;
		container.appendChild(label);

		townField.parentNode.insertBefore(container, townField);

		fetchDivisions('').then(function (items) {
			fillSelect(selProv, items, '— 省/直辖市 —');
		}).catch(function () {});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', buildCascade);
	} else {
		buildCascade();
	}
})();
