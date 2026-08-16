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
// Module JS evolves between versions: never let browsers pin a stale copy
header('Cache-Control: no-cache, must-revalidate');
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
	var socUrl = baseUrl() + 'custom/chinadiv/ajax/soc_division.php';
	var hint = <?php echo json_encode('中国行政区划快捷选择：选定后自动填充省份与城市字段 / Quick select: fills province and city fields'); ?>;

	// Hidden inputs carrying the machine-readable codes; saved by the module trigger
	function ensureHidden(form) {
		['province', 'city', 'district'].forEach(function (k) {
			var name = 'chinadiv_' + k + '_code';
			if (!form.querySelector('input[name="' + name + '"]')) {
				var h = document.createElement('input');
				h.type = 'hidden';
				h.name = name;
				h.value = '';
				form.appendChild(h);
			}
		});
	}

	function setHidden(form, p, c, d) {
		ensureHidden(form);
		var set = function (name, v) {
			var el = form.querySelector('input[name="' + name + '"]');
			if (el) { el.value = v || ''; }
		};
		set('chinadiv_province_code', p);
		set('chinadiv_city_code', c);
		set('chinadiv_district_code', d);
	}

	function socIdFromUrl() {
		// Thirdparty card uses socid=, other object cards use id=
		var m = window.location.search.match(/[?&]socid=(\d+)/) || window.location.search.match(/[?&]id=(\d+)/);
		return m ? m[1] : '';
	}

	function prefillStored(form) {
		var socId = socIdFromUrl();
		if (!socId) { return; }
		fetch(socUrl + '?fk_soc=' + encodeURIComponent(socId), {credentials: 'same-origin'})
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (codes) {
				if (!codes || !codes.province_code) { return; }
				var selects = form.querySelectorAll('.chinadiv-cascade select');
				var selP = selects[0], selC = selects[1], selD = selects[2];
				if (!selP) { return; }
				fetchDivisions('').then(function (items) {
					fillSelect(selP, items, '— 省/直辖市 —');
					selP.value = codes.province_code;
					if (!codes.city_code) { return; }
					return fetchDivisions(codes.province_code).then(function (items2) {
						fillSelect(selC, items2, '— 市 —');
						selC.value = codes.city_code;
						if (!codes.district_code) { return; }
						return fetchDivisions(codes.city_code).then(function (items3) {
							fillSelect(selD, items3, '— 区/县 —');
							selD.value = codes.district_code;
						});
					});
				}).catch(function () {});
				setHidden(form, codes.province_code, codes.city_code, codes.district_code);
			})
			.catch(function () {});
	}

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
		// Keep the current selection across refills (init fill and prefill run concurrently)
		var keep = sel.value;
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
		if (keep && sel.querySelector('option[value="' + keep + '"]')) {
			sel.value = keep;
		}
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
			setHidden(form, selProv.value, '', '');
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
			setHidden(form, selProv.value, selCity.value, '');
			if (!selCity.value) { return; }
			applyTown(form, selCity.options[selCity.selectedIndex].text, '');
			fetchDivisions(selCity.value).then(function (items) {
				fillSelect(selDist, items, '— 区/县 —');
			}).catch(function () {});
		});

		selDist.addEventListener('change', function () {
			if (!selDist.value) { return; }
			setHidden(form, selProv.value, selCity.value, selDist.value);
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

		// Edit form: restore previously stored codes
		prefillStored(form);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', buildCascade);
	} else {
		buildCascade();
	}
})();
