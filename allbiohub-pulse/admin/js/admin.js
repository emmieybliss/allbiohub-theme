/**
 * AllBioHub Pulse admin: story picker, section order, media fields,
 * conditional fields. No dependencies beyond wp.media (when present).
 */
(function () {
	'use strict';

	var cfg = window.pulseAdmin || { i18n: {} };
	var t = cfg.i18n || {};

	function $(sel, ctx) { return (ctx || document).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
	function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

	/* ---------- Story picker ---------- */
	$$('.pulse-picker').forEach(function (picker) {
		var list = $('[data-picker-list]', picker);
		var value = $('[data-picker-value]', picker);
		var input = $('[data-picker-search]', picker);
		var results = $('[data-picker-results]', picker);
		var max = parseInt(picker.getAttribute('data-max'), 10) || 5;
		var type = picker.getAttribute('data-type') || 'post';
		var timer = null;
		var dragged = null;

		function sync() {
			value.value = $$('li', list).map(function (li) { return li.getAttribute('data-id'); }).join(',');
			var full = $$('li', list).length >= max;
			input.disabled = full;
			input.placeholder = full ? (t.max || 'Maximum reached') : (t.search || 'Search posts…');
		}

		function addItem(id, title) {
			if ($('li[data-id="' + id + '"]', list) || $$('li', list).length >= max) { return; }
			var li = document.createElement('li');
			li.setAttribute('data-id', id);
			li.draggable = true;
			li.innerHTML = '<span class="pulse-picker__thumb"></span><span class="pulse-picker__title">' + esc(title) + '</span>' +
				'<span class="pulse-picker__actions"><button type="button" class="button-link" data-picker-up aria-label="' + esc(t.up || 'Move up') + '">↑</button>' +
				'<button type="button" class="button-link" data-picker-down aria-label="' + esc(t.down || 'Move down') + '">↓</button>' +
				'<button type="button" class="button-link pulse-danger" data-picker-remove>' + esc(t.remove || 'Remove') + '</button></span>';
			list.appendChild(li);
			sync();
		}

		list.addEventListener('click', function (e) {
			var li = e.target.closest('li');
			if (!li) { return; }
			if (e.target.closest('[data-picker-remove]')) { li.remove(); sync(); }
			else if (e.target.closest('[data-picker-up]') && li.previousElementSibling) { list.insertBefore(li, li.previousElementSibling); sync(); }
			else if (e.target.closest('[data-picker-down]') && li.nextElementSibling) { list.insertBefore(li.nextElementSibling, li); sync(); }
		});

		list.addEventListener('dragstart', function (e) { dragged = e.target.closest('li'); if (dragged) { dragged.classList.add('is-dragging'); } });
		list.addEventListener('dragend', function () { if (dragged) { dragged.classList.remove('is-dragging'); } dragged = null; sync(); });
		list.addEventListener('dragover', function (e) {
			e.preventDefault();
			var over = e.target.closest('li');
			if (!dragged || !over || over === dragged) { return; }
			var r = over.getBoundingClientRect();
			list.insertBefore(dragged, (e.clientY - r.top) > r.height / 2 ? over.nextSibling : over);
		});

		function search(q) {
			var url = cfg.rest + 'wp/v2/search?per_page=8&type=post&subtype=' + encodeURIComponent(type) + '&search=' + encodeURIComponent(q) + '&_fields=id,title';
			fetch(url, { headers: { 'X-WP-Nonce': cfg.nonce }, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (rows) {
					results.innerHTML = '';
					if (!Array.isArray(rows) || !rows.length) {
						results.innerHTML = '<li class="is-empty">' + esc(t.noResults || 'No matching posts') + '</li>';
						return;
					}
					rows.forEach(function (row) {
						var li = document.createElement('li');
						var b = document.createElement('button');
						b.type = 'button';
						b.textContent = row.title;
						b.addEventListener('click', function () {
							addItem(String(row.id), row.title);
							results.innerHTML = '';
							input.value = '';
							input.focus();
						});
						li.appendChild(b);
						results.appendChild(li);
					});
				})
				.catch(function () { results.innerHTML = ''; });
		}

		input.addEventListener('input', function () {
			clearTimeout(timer);
			var q = input.value.trim();
			if (q.length < 2) { results.innerHTML = ''; return; }
			timer = setTimeout(function () { search(q); }, 250);
		});
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); var b = $('button', results); if (b) { b.click(); } }
			if (e.key === 'Escape') { results.innerHTML = ''; }
		});
		sync();
	});

	/* ---------- Ordered checklist (homepage sections, category sections) ---------- */
	$$('[data-pulse-order]').forEach(function (box) {
		var value = $('[data-order-value]', box);
		var ol = $('ol', box);
		function sync() {
			value.value = $$('li', ol).filter(function (li) { return $('[data-order-check]', li).checked; })
				.map(function (li) { return li.getAttribute('data-slug'); }).join(',');
		}
		box.addEventListener('change', sync);
		box.addEventListener('click', function (e) {
			var li = e.target.closest('li');
			if (!li) { return; }
			if (e.target.closest('[data-order-up]') && li.previousElementSibling) { ol.insertBefore(li, li.previousElementSibling); sync(); e.target.closest('button').focus(); }
			if (e.target.closest('[data-order-down]') && li.nextElementSibling) { ol.insertBefore(li.nextElementSibling, li); sync(); e.target.closest('button').focus(); }
		});
	});

	/* ---------- Media fields ---------- */
	$$('[data-pulse-media]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (!window.wp || !wp.media) { return; }
			var scope = btn.parentNode;
			var input = $('[data-pulse-media-input]', scope);
			var label = $('[data-pulse-media-label]', scope);
			var kind = btn.getAttribute('data-pulse-media');
			var frame = wp.media({ title: btn.textContent, multiple: false, library: { type: kind === 'video' ? 'video' : 'image' } });
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				input.value = a.id;
				if (label) {
					if (a.type === 'image') {
						var src = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
						label.innerHTML = '<img src="' + esc(src) + '" alt="" width="80" height="80" style="object-fit:cover">';
					} else {
						label.textContent = a.filename || a.title;
					}
				}
			});
			frame.open();
		});
	});

	/* ---------- Conditional fields ---------- */
	function conditional(radioSel, attr) {
		var radios = $$(radioSel);
		if (!radios.length) { return; }
		function apply() {
			var cur = radios.filter(function (r) { return r.checked; })[0];
			var val = cur ? cur.value : '';
			$$('[' + attr + ']').forEach(function (el) {
				el.hidden = el.getAttribute(attr).split(' ').indexOf(val) === -1;
			});
		}
		radios.forEach(function (r) { r.addEventListener('change', apply); });
		apply();
	}
	conditional('[data-pulse-source]', 'data-pulse-when');
	conditional('[data-pulse-adtype]', 'data-pulse-when-ad');
}());
