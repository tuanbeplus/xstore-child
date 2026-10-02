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
