/**
 * Vintage Football — front-end behavior
 *
 * - Delivery dates (AJAX)
 * - Mobile club template height
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
		var adminBar = document.getElementById('wpadminbar');
		var adminBarHeight = adminBar ? adminBar.offsetHeight : 0;

		document.documentElement.style.setProperty(
			'--viewport-height',
			(window.innerHeight - adminBarHeight) + 'px'
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
	 * Keep cart and checkout content synchronized after an AJAX add-to-cart.
	 * WooCommerce refreshes mini-cart fragments by default, but cart totals and
	 * checkout order review require their own native refresh events. The storage
	 * listener applies the same update to the other open browser tabs.
	 * ---------------------------------------------------------------------- */
	var insuranceSyncKey = 'vf_shipping_insurance';
	var applyInsuranceChoice = function () {};

	function publishInsuranceChoice(enabled) {
		try {
			localStorage.setItem(insuranceSyncKey, JSON.stringify({
				enabled: enabled,
				at: Date.now()
			}));
		} catch (error) {}
	}

	function readRecentInsuranceChoice() {
		try {
			var saved = JSON.parse(localStorage.getItem(insuranceSyncKey) || '');
			if (!saved || (saved.enabled !== 'yes' && saved.enabled !== 'no')) {
				return '';
			}
			if (Date.now() - saved.at > 8000) {
				return '';
			}
			return saved.enabled;
		} catch (error) {
			return '';
		}
	}

	function refreshCurrentCartView() {
		var recentChoice = readRecentInsuranceChoice();
		if (recentChoice) {
			applyInsuranceChoice(recentChoice);
		}

		if ($('body').hasClass('woocommerce-cart')) {
			$(document.body).trigger('wc_update_cart');
		}

		if ($('body').hasClass('woocommerce-checkout')) {
			$(document.body).trigger('update_checkout');
		}
	}

	function refreshCartPageAfterAddToCart() {
		$(document.body).on('added_to_cart', refreshCurrentCartView);
		$(document).on('change', '.codenitive-insurance-cart-slot input[type="checkbox"][name="codenitive_shipping_insurance"], .codenitive-insurance-checkout-slot input[type="checkbox"][name="codenitive_shipping_insurance"]', function () {
			publishInsuranceChoice(this.checked ? 'yes' : 'no');
		});

		window.addEventListener('storage', function (event) {
			var saved;
			if (event.key === insuranceSyncKey) {
				try {
					saved = JSON.parse(event.newValue || '');
				} catch (error) {
					saved = null;
				}
				if (saved && (saved.enabled === 'yes' || saved.enabled === 'no')) {
					applyInsuranceChoice(saved.enabled);
				}
				return;
			}

			if (event.key && event.key.indexOf('wc_cart_hash_') === 0) {
				refreshCurrentCartView();
			}
		});
	}

	/* -------------------------------------------------------------------------
	 * Render and control the Codenitive insurance toggle inside XStore's mini
	 * cart. The plugin does not natively render its card in that context, so
	 * use its secured refresh and toggle endpoints instead of duplicating fees.
	 * ---------------------------------------------------------------------- */
	function initMiniCartInsurance() {
		var refreshRequest = null;
		var toggleRequest = null;
		var latestInsuranceHtml = '';
		var refreshGeneration = 0;

		function getSlots() {
			/*
			 * XStore's Elementor off-canvas cart does not use the legacy
			 * mini-cart footer hook, and its fragment refresh replaces that
			 * footer from the server. Recreate the slot above Subtotal whenever
			 * the rendered footer no longer contains it.
			 */
			$('.etheme-elementor-off-canvas_content-footer .cart-popup-footer .cart-widget-subtotal, .et_b_header-cart .cart-popup-footer .cart-widget-subtotal').each(function () {
				var $subtotal = $(this);
				if (!$subtotal.prev('.vf-mini-cart-insurance-slot').length) {
					$('<div class="vf-mini-cart-insurance-slot" aria-live="polite"></div>').insertBefore($subtotal);
				}
			});

			return $('.vf-mini-cart-insurance-slot');
		}

		function rememberInsuranceState(enabled) {
			if (!latestInsuranceHtml) {
				return;
			}

			var $markup = $('<div>').html(latestInsuranceHtml);
			$markup.find('input[type="checkbox"][name="codenitive_shipping_insurance"]').each(function () {
				if (enabled === 'yes') {
					this.setAttribute('checked', 'checked');
				} else {
					this.removeAttribute('checked');
				}
			});
			latestInsuranceHtml = $markup.html();
		}

		applyInsuranceChoice = function (enabled) {
			rememberInsuranceState(enabled);
			refreshGeneration++;
			if (refreshRequest) {
				refreshRequest.abort();
			}
			$('input[type="checkbox"][name="codenitive_shipping_insurance"]').each(function () {
				this.checked = enabled === 'yes';
			});
			if (latestInsuranceHtml) {
				getSlots().html(latestInsuranceHtml);
			}
		};

		function refreshMiniCartInsurance() {
			var $slots = getSlots();
			var generation;
			var request;
			if (!window.vfMiniCartInsurance || !$slots.length) {
				return;
			}

			if (latestInsuranceHtml) {
				$slots.html(latestInsuranceHtml);
			}

			if (refreshRequest) {
				refreshRequest.abort();
			}

			generation = ++refreshGeneration;
			request = $.post(window.vfMiniCartInsurance.refreshUrl, {
				nonce: window.vfMiniCartInsurance.nonce,
				context: 'cart'
			}).done(function (response) {
				if (generation !== refreshGeneration) {
					return;
				}
				if (response && response.success && response.data && typeof response.data.html === 'string' && $.trim(response.data.html)) {
					latestInsuranceHtml = response.data.html;
					getSlots().html(latestInsuranceHtml);
				}
			}).always(function () {
				if (refreshRequest === request) {
					refreshRequest = null;
				}
			});
			refreshRequest = request;
		}

		/*
		 * et_calculate_cart_progress fires after XStore replaces the Elementor
		 * mini-cart fragments. Reapply the control there, because that
		 * replacement removes the slot inserted before the request returns.
		 */
		$(document.body).on('wc_fragments_loaded wc_fragments_refreshed added_to_cart removed_from_cart et_calculate_cart_progress et_mini_cart_fragments', refreshMiniCartInsurance);
		$(document).on('click', '.etheme-elementor-off-canvas__toggle_button', refreshMiniCartInsurance);

		$(document).on('change', '.vf-mini-cart-insurance-slot input[type="checkbox"][name="codenitive_shipping_insurance"]', function () {
			var $input = $(this);
			var enabled = $input.is(':checked') ? 'yes' : 'no';
			var $slot = $input.closest('.vf-mini-cart-insurance-slot');

			if (!window.vfMiniCartInsurance || toggleRequest) {
				$input.prop('checked', !$input.is(':checked'));
				return;
			}

			rememberInsuranceState(enabled);
			$slot.find('.codenitive-insurance').addClass('is-loading');
			toggleRequest = $.post(window.vfMiniCartInsurance.toggleUrl, {
				nonce: window.vfMiniCartInsurance.nonce,
				enabled: enabled,
				choice: ''
			}).done(function (response) {
				if (response && response.success) {
					publishInsuranceChoice(enabled);
					$(document.body).trigger('wc_fragment_refresh');
					return;
				}

				rememberInsuranceState(enabled === 'yes' ? 'no' : 'yes');
				$slot.prepend($('<div class="woocommerce-error vf-mini-cart-insurance-error" role="alert"></div>').text(window.vfMiniCartInsurance.errorText));
				refreshMiniCartInsurance();
			}).fail(function () {
				rememberInsuranceState(enabled === 'yes' ? 'no' : 'yes');
				$slot.prepend($('<div class="woocommerce-error vf-mini-cart-insurance-error" role="alert"></div>').text(window.vfMiniCartInsurance.errorText));
				refreshMiniCartInsurance();
			}).always(function () {
				toggleRequest = null;
				$slot.find('.codenitive-insurance').removeClass('is-loading');
			});
		});

		refreshMiniCartInsurance();
	}

	/* -------------------------------------------------------------------------
	 * Boot
	 * ---------------------------------------------------------------------- */

	$(window).on('resize', setMobileClubLayoutHeight);

	function lockBodyScroll() {
		$('html, body').css('overflow', 'hidden');
	}

	function unlockBodyScroll() {
		$('html, body').css('overflow', '');
	}

	$(document).on('click', '.elementor-widget-theme-etheme_mobile_menu a.etheme-elementor-off-canvas__toggle_button', function () {
		setViewportHeight();
		if ($('body').css('overflow') === 'hidden') {
			unlockBodyScroll();
		} else {
			lockBodyScroll();
		}
	});

	$(document).on('click', '.elementor-widget-theme-etheme_mobile_menu .etheme-elementor-off-canvas__close-button', function () {
		unlockBodyScroll();
	});

	$(function () {
		refreshCartPageAfterAddToCart();
		initMiniCartInsurance();
		initOrderDates();
		setMobileClubLayoutHeight();
		initVariationFromUrl();
		initVariationSwatches();
		initSizeGuideLink();
		initHeader();
	});
})(jQuery);
