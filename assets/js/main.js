jQuery(document).ready(function () {
	jQuery.ajax({
		url: order_dates_ajax.ajax_url,
		type: 'POST',
		data: {
			action: 'get_order_dates'
		},
		success: function (response) {
			if (response.success) {
				jQuery('#ajax-order-dates').html(response.data.html);
			}
		}
	});
	if (jQuery(window).width() <= 768) {

		var height_layout_mobile = $('.wrapper-template-mobile-club .woocommerce').height();
		if (height_layout_mobile) {
			jQuery('.wrapper-template-mobile-club').css('height', height_layout_mobile + 'px');
		}
	} else {
		jQuery('.wrapper-template-mobile-club').css('height', 'auto');
	}
});

jQuery(window).on('resize', function () {
	if (jQuery(window).width() <= 768) {
		var height_layout_mobile = $('.wrapper-template-mobile-club .woocommerce').height();
		if (height_layout_mobile) {
			jQuery('.wrapper-template-mobile-club').css('height', height_layout_mobile + 'px');
		}
	} else {
		jQuery('.wrapper-template-mobile-club').css('height', 'auto');
	}
});

/**
 * Auto-select product variation attributes from URL parameters on single product pages.
 * Supports:
 * - ?attr_cutomize=10%20Messi (handles typo)
 * - ?attr_customize=10%20Messi
 * - ?attribute_customize=10%20Messi
 * - ?customize=10%20Messi
 * - ?size=M & ?attr_size=M, etc.
 */
jQuery(function ($) {
	const search = window.location.search;
	if (!search) return;

	const params = new URLSearchParams(search);
	let hasRunScroll = false;

	function normalizeStr(str) {
		return (str || '')
			.toString()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '') // remove accents (e.g. é -> e)
			.replace(/[\u2018\u2019\u201A\u201B']/g, "'") // normalize curly quotes to simple '
			.replace(/\s+/g, ' ')
			.trim()
			.toLowerCase();
	}

	// Strip ALL spaces for ultra-fuzzy matching (e.g. "10Messi" == "10 Messi")
	function compactStr(str) {
		return normalizeStr(str).replace(/\s+/g, '');
	}

	function matchAndSelectOption($select, targetVal) {
		if (!$select.length || !targetVal) return false;

		const cleanTarget = normalizeStr(targetVal);
		const compactTarget = compactStr(targetVal);
		let matchedVal = null;

		$select.find('option').each(function () {
			const optVal = $(this).val();
			const optText = $(this).text();

			if (!optVal) return; // skip placeholder option

			// 1. Exact match value or text
			if (optVal === targetVal || optText === targetVal) {
				matchedVal = optVal;
				return false;
			}

			// 2. Normalized match (case-insensitive, accents, apostrophes)
			if (normalizeStr(optVal) === cleanTarget || normalizeStr(optText) === cleanTarget) {
				matchedVal = optVal;
				return false;
			}

			// 3. Compact match - ignore all spaces (e.g. "10Messi" matches "10 Messi")
			if (compactStr(optVal) === compactTarget || compactStr(optText) === compactTarget) {
				matchedVal = optVal;
				return false;
			}
		});

		if (matchedVal !== null) {
			if ($select.val() !== matchedVal) {
				$select.val(matchedVal).trigger('change');
			}
			return true;
		}
		return false;
	}

	function applyAttributesFromUrl() {
		const $varForm = $('form.variations_form');
		if (!$varForm.length) return;

		let anySelected = false;

		params.forEach(function (val, key) {
			if (!val) return;

			let cleanKey = key.toLowerCase();

			// Handle typo 'cutomize' -> 'customize'
			if (cleanKey.indexOf('cutomize') !== -1) {
				cleanKey = cleanKey.replace('cutomize', 'customize');
			}

			// Extract base attribute name (remove 'attr_' or 'attribute_' prefix)
			const attrName = cleanKey.replace(/^(attr_|attribute_)/, '');

			// Find select element by attribute name, data-attribute_name, id, or direct name
			let $select = $varForm.find('select[name="attribute_' + attrName + '"], select[data-attribute_name="attribute_' + attrName + '"], select#' + attrName);
			if (!$select.length) {
				$select = $varForm.find('select[name="' + cleanKey + '"], select#' + cleanKey);
			}

			if ($select.length) {
				if (matchAndSelectOption($select, val)) {
					anySelected = true;
				}
			}
		});

		// Optional smooth scroll to variations form on initial auto-select
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

	// Run when WooCommerce variation form initializes
	$(document).on('wc_variation_form', '.variations_form', function () {
		applyAttributesFromUrl();
	});

	// Run on ready and staggered timeouts to ensure matching after any async scripts
	applyAttributesFromUrl();
	setTimeout(applyAttributesFromUrl, 150);
	setTimeout(applyAttributesFromUrl, 500);
});

/**
 * Product Variations - List Button UI (Matches Category Filter Pills)
 * Converts WooCommerce select dropdowns in table.variations to clickable swatch buttons
 * while maintaining 100% two-way sync with WooCommerce variations and custom field logic.
 */
jQuery(function ($) {
	function isOptionAvailable($option) {
		if (!$option.length) return false;
		if ($option.is(':disabled') || $option.prop('disabled')) return false;
		if ($option.hasClass('disabled')) return false;
		if ($option.hasClass('attached') && !$option.hasClass('enabled')) return false;
		return true;
	}

	function initVariationSwatches() {
		const $forms = $('form.variations_form');
		if (!$forms.length) return;

		$forms.each(function () {
			const $form = $(this);
			const $selects = $form.find('table.variations select');

			$selects.each(function () {
				const $select = $(this);
				let $btnWrap = $select.siblings('.vf-attr-buttons');

				if (!$btnWrap.length) {
					$btnWrap = $('<div class="vf-attr-buttons" role="radiogroup"></div>');
					$select.after($btnWrap);
				}

				function syncButtons() {
					const currentVal = $select.val();
					$btnWrap.find('.vf-attr-btn').each(function () {
						const $btn = $(this);
						const btnVal = $btn.attr('data-value');

						const $opt = $select.find('option').filter(function () {
							return $(this).val() === btnVal;
						});

						if (!$opt.length || !isOptionAvailable($opt)) {
							$btn.addClass('is-disabled').attr('aria-disabled', 'true');
						} else {
							$btn.removeClass('is-disabled').removeAttr('aria-disabled');
						}

						if (currentVal && btnVal === currentVal) {
							$btn.addClass('is-active').attr('aria-checked', 'true');
						} else {
							$btn.removeClass('is-active').attr('aria-checked', 'false');
						}
					});
				}

				function buildButtons() {
					$btnWrap.empty();
					$select.find('option').each(function () {
						const $opt = $(this);
						const val = $opt.val();
						if (!val) return; // skip placeholder option

						const text = $opt.text().trim();
						const $btn = $('<button type="button" class="vf-attr-btn"></button>')
							.attr('data-value', val)
							.attr('aria-label', text)
							.text(text);

						$btnWrap.append($btn);
					});

					// Append Size Guide link right after the last option of size
					const attrName = (
						$select.attr('name') ||
						$select.attr('id') ||
						$select.attr('data-attribute_name') ||
						''
					).toLowerCase();
					if (attrName.indexOf('size') !== -1) {
						const $sizeGuideLink = $(
							'<a href="#tab-size_guide" class="vf-size-guide-link">' +
							'<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"></path><path d="m14.5 12.5 2-2"></path><path d="m11.5 9.5 2-2"></path><path d="m8.5 6.5 2-2"></path><path d="m17.5 15.5 2-2"></path></svg>' +
							'<span>Size Guide</span>' +
							'</a>'
						);
						$btnWrap.append($sizeGuideLink);
					}

					syncButtons();
				}

				if (!$select.data('vf_swatches_inited')) {
					$select.data('vf_swatches_inited', true);

					buildButtons();

					// Button click interaction
					$btnWrap.on('click', '.vf-attr-btn', function (e) {
						e.preventDefault();
						const $btn = $(this);

						if ($btn.hasClass('is-disabled')) {
							return;
						}

						const val = $btn.attr('data-value');

						// Toggle selection: deselect if already active, otherwise select
						if ($btn.hasClass('is-active')) {
							$select.val('').trigger('change');
						} else {
							$select.val(val).trigger('change');
						}
					});

					// Update swatches on native select change
					$select.on('change.vfSwatches', function () {
						syncButtons();
					});

					$select.data('vfSyncButtons', syncButtons);
					$select.data('vfBuildButtons', buildButtons);
				} else {
					// Check if option elements were re-populated
					const optCount = $select.find('option').filter(function () {
						return !!$(this).val();
					}).length;
					const btnCount = $btnWrap.find('.vf-attr-btn').length;

					if (optCount !== btnCount) {
						buildButtons();
					} else {
						syncButtons();
					}
				}
			});

			// Bind form variation lifecycle events once per form
			if (!$form.data('vf_form_swatches_inited')) {
				$form.data('vf_form_swatches_inited', true);

				$form.on(
					'woocommerce_update_variation_values check_variations reset_data woocommerce_variation_has_changed',
					function () {
						$form.find('table.variations select').each(function () {
							const sync = $(this).data('vfSyncButtons');
							if (typeof sync === 'function') {
								sync();
							}
						});
					}
				);
			}
		});
	}

	// Handle Size Guide link click: activate WooCommerce/XStore tab and smooth scroll with 100px offset
	$(document).on('click', '.vf-size-guide-link', function (e) {
		e.preventDefault();
		e.stopPropagation();

		const $tabLink = $('.woocommerce-tabs a[href="#tab-size_guide"], #tab-title-size_guide a, .size_guide_tab a, [aria-controls="tab-size_guide"]');
		const $tabTitle = $('#tab-title-size_guide, .size_guide_tab');
		const $tabPanel = $('#tab-size_guide');
		const $tabsWrapper = $('.woocommerce-tabs');

		// 1. Activate tab via WooCommerce / XStore tab click handler
		if ($tabLink.length) {
			$tabLink.trigger('click');
		} else if ($tabTitle.length) {
			$tabTitle.trigger('click');
		}

		// 2. Direct fallback activation to guarantee tab panel is visible
		if ($tabsWrapper.length && $tabPanel.length) {
			$tabsWrapper.find('.wc-tabs li, ul.tabs li').removeClass('active');
			$tabTitle.addClass('active');
			$tabsWrapper.find('.wc-tab, .woocommerce-Tabs-panel').hide();
			$tabPanel.show();
		}

		// 3. Update URL hash without causing a native jump
		if (window.history && window.history.pushState) {
			window.history.pushState(null, '', '#tab-size_guide');
		}

		// 4. Calculate accurate position after DOM reflow and scroll instantly with exact 100px offset
		setTimeout(function () {
			let targetTop = 0;
			const $target = ($tabTitle.length && $tabTitle.is(':visible'))
				? $tabTitle
				: ($tabPanel.length && $tabPanel.is(':visible') ? $tabPanel : $tabsWrapper);

			if ($target.length) {
				targetTop = $target.offset().top;
			}

			if (targetTop > 0) {
				const scrollPosition = Math.max(0, targetTop - 100);
				window.scrollTo({
					top: scrollPosition,
					behavior: 'smooth'
				});
			}
		}, 30);
	});

	// Initialize on DOM ready
	initVariationSwatches();

	// Initialize on WooCommerce variation form setup
	$(document).on('wc_variation_form', '.variations_form', function () {
		initVariationSwatches();
	});

	// Staggered checks to accommodate any asynchronous scripts
	setTimeout(initVariationSwatches, 100);
	setTimeout(initVariationSwatches, 400);
});

