/**
 * AllBioHub Pulse front end. No dependencies.
 */
(function () {
	'use strict';

	var doc = document;
	var root = doc.documentElement;
	var cfg = window.pulseSite || { i18n: {} };
	var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }

	/* ---------- Dialogs: drawer + search ---------- */
	var lastFocus = null;

	function openDialog(el, opener) {
		if (!el) { return; }
		lastFocus = opener || doc.activeElement;
		el.hidden = false;
		root.classList.add('pulse-locked');
		$$('[aria-controls="' + el.id + '"]').forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
		var first = $('input, a, button', el);
		if (first) { first.focus(); }
	}

	function closeDialog(el) {
		if (!el || el.hidden) { return; }
		el.hidden = true;
		root.classList.remove('pulse-locked');
		$$('[aria-controls="' + el.id + '"]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
		if (lastFocus) { lastFocus.focus(); }
	}

	var drawer = $('#pulse-drawer');
	var search = $('#pulse-search');

	$$('[data-pulse-drawer-open]').forEach(function (b) { b.addEventListener('click', function () { openDialog(drawer, b); }); });
	$$('[data-pulse-drawer-close]').forEach(function (b) { b.addEventListener('click', function () { closeDialog(drawer); }); });
	$$('[data-pulse-search-open]').forEach(function (b) { b.addEventListener('click', function () { openDialog(search, b); }); });
	$$('[data-pulse-search-close]').forEach(function (b) { b.addEventListener('click', function () { closeDialog(search); }); });

	doc.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			closeDialog(drawer);
			closeDialog(search);
			$$('.pulse-nav li.is-open').forEach(function (li) { toggleSub(li, false); });
			return;
		}
		// Keep focus inside an open dialog.
		if (e.key === 'Tab') {
			var open = drawer && !drawer.hidden ? drawer : (search && !search.hidden ? search : null);
			if (!open) { return; }
			var f = $$('a[href], button, input, select, textarea', open).filter(function (n) { return n.offsetParent !== null; });
			if (!f.length) { return; }
			if (e.shiftKey && doc.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
			else if (!e.shiftKey && doc.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
		}
	});

	/* ---------- Submenus ---------- */
	function toggleSub(li, force) {
		var open = typeof force === 'boolean' ? force : !li.classList.contains('is-open');
		li.classList.toggle('is-open', open);
		var btn = $(':scope > .pulse-subtoggle', li);
		if (btn) { btn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
	}
	$$('.pulse-subtoggle').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			toggleSub(btn.parentNode);
		});
	});

	/* ---------- Colour scheme ---------- */
	var schemeBtn = $('[data-pulse-scheme]');
	function effectiveScheme() {
		var set = root.getAttribute('data-theme');
		if (set) { return set; }
		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	}
	if (schemeBtn) {
		schemeBtn.addEventListener('click', function () {
			var next = effectiveScheme() === 'dark' ? 'light' : 'dark';
			root.setAttribute('data-theme', next);
			try { localStorage.setItem('pulse-scheme', next); } catch (e) {}
			var label = $('[data-pulse-scheme-label]', schemeBtn);
			if (label) { label.textContent = next === 'dark' ? 'Dark mode on' : 'Light mode on'; }
		});
	}

	/* ---------- Header: hide ticker when scrolling down ---------- */
	var header = $('[data-pulse-header]');
	if (header && doc.body.classList.contains('pulse-sticky-header')) {
		var lastY = window.scrollY;
		var ticking = false;
		window.addEventListener('scroll', function () {
			if (ticking) { return; }
			ticking = true;
			window.requestAnimationFrame(function () {
				var y = window.scrollY;
				header.classList.toggle('is-scrolled-down', y > 200 && y > lastY);
				lastY = y;
				ticking = false;
			});
		}, { passive: true });
	}

	/* ---------- Ticker ---------- */
	$$('.pulse-ticker').forEach(function (ticker) {
		var track = $('.pulse-ticker__track', ticker);
		var toggle = $('[data-pulse-ticker-toggle]', ticker);
		if (!track || reduced) { return; }
		// Duplicate items for a seamless loop; clones are hidden from assistive tech.
		$$('li', track).forEach(function (li) {
			var c = li.cloneNode(true);
			c.setAttribute('aria-hidden', 'true');
			$$('a', c).forEach(function (a) { a.tabIndex = -1; });
			track.appendChild(c);
		});
		var px = track.scrollWidth / 2;
		ticker.style.setProperty('--pulse-ticker-duration', Math.max(20, Math.round(px / 50)) + 's');
		ticker.classList.add('is-running');
		if (toggle) {
			toggle.addEventListener('click', function () {
				var paused = ticker.classList.toggle('is-paused');
				toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
			});
		}
	});

	/* ---------- Video facade: iframe only after a click ---------- */
	doc.addEventListener('click', function (e) {
		var btn = e.target.closest && e.target.closest('.pulse-video__play');
		if (!btn) { return; }
		var box = btn.closest('.pulse-video');
		var src = box && box.getAttribute('data-embed');
		if (!src) { return; }
		var f = doc.createElement('iframe');
		f.src = src;
		f.title = box.getAttribute('data-title') || '';
		f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
		f.allowFullscreen = true;
		f.referrerPolicy = 'strict-origin-when-cross-origin';
		box.innerHTML = '';
		box.appendChild(f);
		box.classList.add('is-playing');
		f.focus();
	});

	/* ---------- Copy link ---------- */
	$$('[data-pulse-copy]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var url = btn.getAttribute('data-pulse-copy');
			var done = function () {
				btn.classList.add('is-copied');
				var sr = $('.screen-reader-text', btn);
				var old = sr ? sr.textContent : '';
				if (sr) { sr.textContent = cfg.i18n.copied || 'Link copied'; }
				setTimeout(function () { btn.classList.remove('is-copied'); if (sr) { sr.textContent = old; } }, 2000);
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url).then(done, function () {});
			} else {
				window.prompt('', url);
			}
		});
	});

	/* ---------- TOC: highlight the current section ---------- */
	var tocLinks = $$('.pulse-toc a[href^="#"]');
	if (tocLinks.length && 'IntersectionObserver' in window) {
		var byId = {};
		tocLinks.forEach(function (a) { byId[decodeURIComponent(a.getAttribute('href').slice(1))] = a; });
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting && byId[en.target.id]) {
					tocLinks.forEach(function (a) { a.classList.remove('is-active'); });
					byId[en.target.id].classList.add('is-active');
				}
			});
		}, { rootMargin: '0px 0px -70% 0px' });
		Object.keys(byId).forEach(function (id) { var h = doc.getElementById(id); if (h) { io.observe(h); } });
	}

	/* ---------- Beacons: views + ad impressions ---------- */
	function beacon(url) {
		if (!url) { return; }
		try {
			if (navigator.sendBeacon) { navigator.sendBeacon(url); return; }
		} catch (e) {}
		if (window.fetch) { fetch(url, { method: 'POST', keepalive: true, credentials: 'omit' }).catch(function () {}); }
	}

	if (cfg.viewUrl) {
		// Count a view once the reader has stayed a moment, not on bounce.
		setTimeout(function () { if (doc.visibilityState !== 'hidden') { beacon(cfg.viewUrl); } }, 4000);
	}

	var ads = $$('[data-pulse-imp]');
	if (ads.length && 'IntersectionObserver' in window) {
		var seen = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting && en.intersectionRatio >= 0.5) {
					beacon(en.target.getAttribute('data-pulse-imp'));
					seen.unobserve(en.target);
				}
			});
		}, { threshold: 0.5 });
		ads.forEach(function (a) { seen.observe(a); });
	}
}());
