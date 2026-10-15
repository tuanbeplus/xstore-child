/**
 * Vintage Football — front-end behavior
 *
 * - Delivery dates (AJAX)
 * - Mobile club template height
 * - Header currency Select2
 * - Variation preselect from URL params
 * - Variation swatch buttons and Size Guide link
 * - Sticky header, header height, viewport height
 */
(function ($) {
	'use strict';

	var MOBILE_MAX_WIDTH = 768;

	/* -------------------------------------------------------------------------
	 * Delivery dates
	 * ---------------------------------------------------------------------- */

	function initOrderDates() {
		if (typeof order_dates_ajax === 'undefined' || !order_dates_ajax.ajax_url) {
			return;
		}

		$.ajax({
			url: order_dates_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'get_order_dates'
			},
			success: function (response) {
				if (response.success) {
					$('#ajax-order-dates').html(response.data.html);
				}
			}
		});
	}

	/* -------------------------------------------------------------------------
	 * Mobile club template height
	 * ---------------------------------------------------------------------- */

	function setMobileClubLayoutHeight() {
		var $wrapper = $('.wrapper-template-mobile-club');
		var layoutHeight;

		if ($(window).width() <= MOBILE_MAX_WIDTH) {
			layoutHeight = $wrapper.find('.woocommerce').height();
			if (layoutHeight) {
				$wrapper.css('height', layoutHeight + 'px');
			}
			return;
		}

		$wrapper.css('height', 'auto');
	}

	/* -------------------------------------------------------------------------
	 * Currency selector
	 * ---------------------------------------------------------------------- */

	var SELECT2_BASE = 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/';

	function currencyOptionLabel(state) {
		if (!state.id) {
			return state.text;
		}
		return $('<span></span>').text(state.text);
	}

	function initCurrencySelect() {
		var $currencyWrap = $('.wrapper-header-currency');
		var $currencySelect = $currencyWrap.find('form select');

		if (!$currencySelect.length || !$.fn.select2 || $currencySelect.data('select2')) {
			return;
		}

		$currencySelect.select2({
			width: '120px',
			minimumResultsForSearch: Infinity,
			dropdownParent: $currencyWrap,
			templateResult: currencyOptionLabel,
			templateSelection: currencyOptionLabel
		});
	}

	function loadCurrencySelectAssets() {
		var cssHref = SELECT2_BASE + 'css/select2.min.css';
		var scriptSrc = SELECT2_BASE + 'js/select2.min.js';

		if (!document.querySelector('link[data-vf-select2]')) {
			$('<link>', {
				rel: 'stylesheet',
				href: cssHref,
				'data-vf-select2': '1'
			}).appendTo(document.head);
		}

		if ($.fn.select2) {
			initCurrencySelect();
			return;
		}

		$.ajax({
			url: scriptSrc,
			dataType: 'script',
			cache: true
		}).done(initCurrencySelect);
	}

	function scheduleCurrencySelect() {
		var $currencySelect = $('.wrapper-header-currency form select');
		var started = false;

		if (!$currencySelect.length) {
			return;
		}

		function start() {
			if (started) {
				return;
			}
			started = true;
			loadCurrencySelectAssets();
		}

		$currencySelect.one('focus pointerdown', start);

		if ('requestIdleCallback' in window) {
			window.requestIdleCallback(start, { timeout: 2500 });
			return;
		}

		$(window).on('load', function () {
			window.setTimeout(start, 1200);
		});
	}

	/* -------------------------------------------------------------------------
	 * Variation attributes from URL
	 * ?size=M, ?customize=10%20Messi, ?attr_cutomize=... (typo), etc.
	 * ---------------------------------------------------------------------- */

	function normalizeStr(str) {
		return (str || '')
			.toString()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[\u2018\u2019\u201A\u201B']/g, "'")
			.replace(/\s+/g, ' ')
			.trim()
			.toLowerCase();
	}

	function compactStr(str) {
		return normalizeStr(str).replace(/\s+/g, '');
	}

	function matchAndSelectOption($select, targetVal) {
		var cleanTarget = normalizeStr(targetVal);
		var compactTarget = compactStr(targetVal);
		var matchedVal = null;

		if (!$select.length || !targetVal) {
			return false;
		}

		$select.find('option').each(function () {
			var optVal = $(this).val();
			var optText = $(this).text();

			if (!optVal) {
				return;
			}

			if (optVal === targetVal || optText === targetVal) {
				matchedVal = optVal;
				return false;
			}

			if (normalizeStr(optVal) === cleanTarget || normalizeStr(optText) === cleanTarget) {
				matchedVal = optVal;
				return false;
			}

			if (compactStr(optVal) === compactTarget || compactStr(optText) === compactTarget) {
				matchedVal = optVal;
				return false;
			}
		});

		if (matchedVal === null) {
			return false;
		}

		if ($select.val() !== matchedVal) {
			$select.val(matchedVal).trigger('change');
		}

		return true;
	}

	function initVariationFromUrl() {
		var search = window.location.search;
		var params;
		var hasRunScroll = false;

		if (!search) {
			return;
		}

		params = new URLSearchParams(search);

		function applyAttributesFromUrl() {
			var $varForm = $('form.variations_form');
			var anySelected = false;

			if (!$varForm.length) {
				return;
			}

			params.forEach(function (val, key) {
				var cleanKey;
				var attrName;
				var $select;

				if (!val) {
					return;
				}

				cleanKey = key.toLowerCase();

				if (cleanKey.indexOf('cutomize') !== -1) {
					cleanKey = cleanKey.replace('cutomize', 'customize');
				}

				attrName = cleanKey.replace(/^(attr_|attribute_)/, '');

				$select = $varForm.find(
					'select[name="attribute_' + attrName + '"], ' +
					'select[data-attribute_name="attribute_' + attrName + '"], ' +
					'select#' + attrName
				);

				if (!$select.length) {
					$select = $varForm.find('select[name="' + cleanKey + '"], select#' + cleanKey);
				}

				if ($select.length && matchAndSelectOption($select, val)) {
					anySelected = true;
				}
			});

			if (anySelected && !hasRunScroll) {
				hasRunScroll = true;
				setTimeout(function () {
					if ($varForm.length && $(window).scrollTop() < $varForm.offset().top - 300) {
						$('html, body').animate({
							scrollTop: $varForm.offset().top - 120
						}, 400);
					}
				}, 200);
			}
		}

		$(document).on('wc_variation_form', '.variations_form', function () {
			applyAttributesFromUrl();
		});

		applyAttributesFromUrl();
		setTimeout(applyAttributesFromUrl, 150);
		setTimeout(applyAttributesFromUrl, 500);
	}

	/* -------------------------------------------------------------------------
	 * Variation swatch buttons
	 * ---------------------------------------------------------------------- */

	var SIZE_GUIDE_LINK_HTML =
		'<a href="#tab-size_guide" class="vf-size-guide-link">' +
		'<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"></path><path d="m14.5 12.5 2-2"></path><path d="m11.5 9.5 2-2"></path><path d="m8.5 6.5 2-2"></path><path d="m17.5 15.5 2-2"></path></svg>' +
		'<span>Size Guide</span>' +
		'</a>';

	function isOptionAvailable($option) {
		if (!$option.length) {
			return false;
		}
		if ($option.is(':disabled') || $option.prop('disabled')) {
			return false;
		}
		if ($option.hasClass('disabled')) {
			return false;
		}
		if ($option.hasClass('attached') && !$option.hasClass('enabled')) {
			return false;
		}
		return true;
	}

	function initVariationSwatches() {
		var $forms = $('form.variations_form');

		if (!$forms.length) {
			return;
		}

		$forms.each(function () {
			var $form = $(this);

			$form.find('table.variations select').each(function () {
				var $select = $(this);
				var $btnWrap = $select.siblings('.vf-attr-buttons');

				if (!$btnWrap.length) {
					$btnWrap = $('<div class="vf-attr-buttons" role="radiogroup"></div>');
					$select.after($btnWrap);
				}

				var pendingValue = null;
				var suppressClickUntil = 0;
				var commitToken = 0;
				var pointerStart = null;

				function syncButtons() {
					var currentVal = pendingValue !== null ? pendingValue : ($select.val() || '');
					var optionsByValue = {};

					$select.find('option').each(function () {
						if (this.value) {
							optionsByValue[this.value] = this;
						}
					});

					$btnWrap.find('.vf-attr-btn').each(function () {
						var $btn = $(this);
						var btnVal = this.getAttribute('data-value');
						var option = optionsByValue[btnVal];
						var isChosen = !!currentVal && btnVal === currentVal;

						if (!option || !isOptionAvailable($(option))) {
							$btn.addClass('is-disabled').attr('aria-disabled', 'true');
						} else {
							$btn.removeClass('is-disabled').removeAttr('aria-disabled');
						}

						$btn.toggleClass('is-active', isChosen).attr('aria-checked', isChosen ? 'true' : 'false');
					});
				}

				function scheduleSync() {
					if ($select.data('vfSyncFrame')) {
						return;
					}

					$select.data('vfSyncFrame', window.requestAnimationFrame(function () {
						$select.removeData('vfSyncFrame');
						syncButtons();
					}));
				}

				function commitSelect(nextVal) {
					var scrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
					var rootStyle = document.documentElement.style;
					var previousScrollBehavior = rootStyle.scrollBehavior;

					if ($select.val() !== nextVal) {
						$select.val(nextVal).trigger('change');
					}

					if ((window.pageYOffset || document.documentElement.scrollTop || 0) !== scrollY) {
						rootStyle.scrollBehavior = 'auto';
						window.scrollTo(0, scrollY);
						rootStyle.scrollBehavior = previousScrollBehavior;
					}
				}

				function nextValueFor($btn) {
					var current = pendingValue !== null ? pendingValue : ($select.val() || '');
					var value = $btn.attr('data-value') || '';

					return current === value ? '' : value;
				}

				function queueCommit(nextVal) {
					var token = ++commitToken;

					window.requestAnimationFrame(function () {
						window.setTimeout(function () {
							if (token !== commitToken) {
								return;
							}
							pendingValue = null;
							commitSelect(nextVal);
						}, 0);
					});
				}

				function clearPending() {
					commitToken += 1;
					pendingValue = null;
					pointerStart = null;
					syncButtons();
				}

				function activateButton($btn) {
					if ($btn.hasClass('is-disabled')) {
						return;
					}

					pendingValue = nextValueFor($btn);
					syncButtons();
					queueCommit(pendingValue);
				}

				function touchMoved(event) {
					if (!pointerStart) {
						return true;
					}

					return Math.abs(event.clientX - pointerStart.x) > 8 || Math.abs(event.clientY - pointerStart.y) > 8;
				}

				function buildButtons() {
					var attrName;

					$btnWrap.empty();

					$select.find('option').each(function () {
						var $opt = $(this);
						var val = $opt.val();
						var text;
						var $btn;

						if (!val) {
							return;
						}

						text = $opt.text().trim();
						$btn = $('<button type="button" class="vf-attr-btn"></button>')
							.attr('data-value', val)
							.attr('aria-label', text)
							.text(text);

						$btnWrap.append($btn);
					});

					attrName = (
						$select.attr('name') ||
						$select.attr('id') ||
						$select.attr('data-attribute_name') ||
						''
					).toLowerCase();

					if (attrName.indexOf('size') !== -1) {
						$btnWrap.append($(SIZE_GUIDE_LINK_HTML));
					}

					syncButtons();
				}

				if (!$select.data('vf_swatches_inited')) {
					$select.data('vf_swatches_inited', true);
					buildButtons();

					$btnWrap.on('pointerdown', '.vf-attr-btn', function (e) {
						var $btn = $(this);

						if (e.pointerType === 'mouse' || $btn.hasClass('is-disabled')) {
							return;
						}

						if (this.setPointerCapture) {
							try {
								this.setPointerCapture(e.pointerId);
							} catch (err) {
								// Pointer can already be inactive if the touch was cancelled.
							}
						}

						pointerStart = {
							x: e.clientX,
							y: e.clientY,
							id: e.pointerId
						};
						suppressClickUntil = Date.now() + 700;
						pendingValue = nextValueFor($btn);
						syncButtons();
					});

					// Commit on finger-up. The later click is ignored so the choice is not toggled twice.
					$btnWrap.on('pointerup', '.vf-attr-btn', function (e) {
						var nextVal;

						if (!pointerStart || pointerStart.id !== e.pointerId || e.pointerType === 'mouse') {
							return;
						}

						if (touchMoved(e)) {
							clearPending();
							return;
						}

						nextVal = pendingValue;
						pointerStart = null;
						suppressClickUntil = Date.now() + 700;
						queueCommit(nextVal);
					});

					$btnWrap.on('pointercancel', '.vf-attr-btn', clearPending);

					$btnWrap.on('click', '.vf-attr-btn', function (e) {
						e.preventDefault();

						if (Date.now() < suppressClickUntil) {
							return;
						}

						activateButton($(this));
					});

					$select.on('change.vfSwatches', scheduleSync);

					$select.data('vfSyncButtons', scheduleSync);
					$select.data('vfBuildButtons', buildButtons);
					return;
				}

				var optionCount = $select.find('option').filter(function () {
					return !!$(this).val();
				}).length;
				var buttonCount = $btnWrap.find('.vf-attr-btn').length;

				if (optionCount !== buttonCount) {
					buildButtons();
				} else {
					syncButtons();
				}
			});

			if ($form.data('vf_form_swatches_inited')) {
				return;
			}

			$form.data('vf_form_swatches_inited', true);
			$form.on(
				'woocommerce_update_variation_values check_variations reset_data woocommerce_variation_has_changed',
				function () {
					$form.find('table.variations select').each(function () {
						var sync = $(this).data('vfSyncButtons');
						if (typeof sync === 'function') {
							sync();
						}
					});
				}
			);
		});
	}

	function initSizeGuideLink() {
		$(document).on('click', '.vf-size-guide-link', function (e) {
			var $tabLink = $('.woocommerce-tabs a[href="#tab-size_guide"], #tab-title-size_guide a, .size_guide_tab a, [aria-controls="tab-size_guide"]');
			var $tabTitle = $('#tab-title-size_guide, .size_guide_tab');
			var $tabPanel = $('#tab-size_guide');
			var $tabsWrapper = $('.woocommerce-tabs');

			e.preventDefault();
			e.stopPropagation();

			if ($tabLink.length) {
				$tabLink.trigger('click');
			} else if ($tabTitle.length) {
				$tabTitle.trigger('click');
			}

			if ($tabsWrapper.length && $tabPanel.length) {
				$tabsWrapper.find('.wc-tabs li, ul.tabs li').removeClass('active');
				$tabTitle.addClass('active');
				$tabsWrapper.find('.wc-tab, .woocommerce-Tabs-panel').hide();
				$tabPanel.show();
			}

			if (window.history && window.history.pushState) {
				window.history.pushState(null, '', '#tab-size_guide');
			}

			setTimeout(function () {
				var $target = ($tabTitle.length && $tabTitle.is(':visible'))
					? $tabTitle
					: ($tabPanel.length && $tabPanel.is(':visible') ? $tabPanel : $tabsWrapper);
				var targetTop = $target.length ? $target.offset().top : 0;

				if (targetTop > 0) {
					window.scrollTo({
						top: Math.max(0, targetTop - 100),
						behavior: 'smooth'
					});
				}
			}, 30);
		});
	}

	/* -------------------------------------------------------------------------
	 * Header height, sticky state, viewport height
	 * ---------------------------------------------------------------------- */

	var headerWasSticky = false;

	function getHeader() {
		return $('header.elementor-location-header');
	}

	function setHeaderHeight() {
		var siteHeader = getHeader();
		var topBar;
		var topBarHeight;
		var height;
		var root;

		if (!siteHeader.length) {
			return;
		}

		topBar = siteHeader.find('#header-top-bar').filter(':visible').first();

		if (!topBar.length) {
			topBar = siteHeader
				.find('.elementor-top-section, .e-parent')
				.filter(':visible')
				.first();
		}

		topBarHeight = topBar.length
			? Math.round(topBar[0].getBoundingClientRect().height)
			: 0;
		height = siteHeader[0].offsetHeight;
		root = document.documentElement.style;

		root.setProperty('--header-height', height + 'px');
		root.setProperty('--topbar-height', topBarHeight + 'px');
	}

	function handleStickyHeader() {
		var $body = $('body');
		var siteHeader = getHeader();
		var y;
		var shouldSticky;
		var reducedMotion;

		if (!siteHeader.length) {
			return;
		}

		y = window.pageYOffset || document.documentElement.scrollTop || 0;
		shouldSticky = y > 15;
		reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		if (shouldSticky && !headerWasSticky) {
			$body.addClass('sticky');
			siteHeader.addClass('is-sticky');

			if (!reducedMotion) {
				siteHeader.addClass('vf-header-sticky-enter');
				siteHeader.one('animationend webkitAnimationEnd', function () {
					siteHeader.removeClass('vf-header-sticky-enter');
				});
			}
		} else if (!shouldSticky) {
			$body.removeClass('sticky');
			siteHeader.removeClass('is-sticky vf-header-sticky-enter');
		} else {
			$body.addClass('sticky');
			siteHeader.addClass('is-sticky');
		}

		headerWasSticky = shouldSticky;
	}

	function setViewportHeight() {
		document.documentElement.style.setProperty(
			'--viewport-height',
			window.innerHeight + 'px'
		);
	}

	function unlockHeader() {
		document.body.classList.remove('vf-loading');
	}

	function initHeader() {
		var unlockTimer;

		setHeaderHeight();
		setViewportHeight();
		handleStickyHeader();

		unlockTimer = setTimeout(unlockHeader, 8000);

		$(window).on('load', function () {
			clearTimeout(unlockTimer);
			unlockHeader();
			setHeaderHeight();
			setTimeout(setHeaderHeight, 300);
			setTimeout(setHeaderHeight, 1000);
		});

		$(window).on('resize orientationchange', function () {
			setHeaderHeight();
			setViewportHeight();
			handleStickyHeader();
		});

		window.addEventListener('scroll', handleStickyHeader, { passive: true });
	}

	/* -------------------------------------------------------------------------
	 * Boot
	 * ---------------------------------------------------------------------- */

	$(window).on('resize', setMobileClubLayoutHeight);

	$(function () {
		initOrderDates();
		setMobileClubLayoutHeight();
		scheduleCurrencySelect();
		initVariationFromUrl();
		initVariationSwatches();
		initSizeGuideLink();
		initHeader();
	});
})(jQuery);
