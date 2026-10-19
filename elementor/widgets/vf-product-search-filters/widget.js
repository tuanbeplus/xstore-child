/**
 * VF Product Search Filters
 * Refreshes the WooCommerce product loop in place.
 */
(function ($) {
    'use strict';

    function init($scope) {
        var $root = $scope.find('.vf-ps').first();
        if (!$root.length || $root.data('vfPsReady')) {
            return;
        }
        $root.data('vfPsReady', true);

        var $results = $root.find('.vf-ps-results');
        var $search = $root.find('.vf-ps-search-input');
        var searchParam = $root.data('search-param') || 'q';
        var initialUrl = new URL(window.location.href);
        if (initialUrl.searchParams.has('s')) {
            searchParam = 's';
        }
        var priceMax = parseInt($root.data('price-max'), 10) || 0;
        var contextCat = parseInt($root.data('context-cat'), 10) || 0;
        var perPage = parseInt($root.data('per-page'), 10) || 12;
        var columns = parseInt($root.data('columns'), 10) || 3;
        var paginate = $root.data('paginate') || 'yes';
        var emptyText = $root.attr('data-empty') || '';
        var defaultOrder = $root.attr('data-orderby') || 'popularity';
        var request = null;
        var searchTimer = null;
        var priceTimer = null;

        function checkedValues(name) {
            return $root.find('input[name="' + name + '"]:checked').map(function () {
                return this.value;
            }).get();
        }

        function priceValues() {
            var min = parseInt($root.find('.vf-ps-min').val(), 10) || 0;
            var max = parseInt($root.find('.vf-ps-max').val(), 10) || 0;
            return { min: min, max: max };
        }

        function buildUrl(pageUrl) {
            var url = new URL(pageUrl || window.location.href);
            url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, '/');
            url.searchParams.delete('paged');

            ['vf_q', 'vf_continent', 'vf_league', 'vf_club', 'vf_legend', 'vf_size', 'vf_cat', 'sizes', 'size'].forEach(function (key) {
                url.searchParams.delete(key);
            });

            var groups = {
                continents: checkedValues('continents'),
                leagues: checkedValues('leagues'),
                clubs: checkedValues('clubs'),
                nations: checkedValues('nations'),
                legends: checkedValues('legends'),
                decades: checkedValues('decades'),
                more: checkedValues('more')
            };

            Object.keys(groups).forEach(function (key) {
                if (groups[key].length) {
                    url.searchParams.set(key, groups[key].join(','));
                } else {
                    url.searchParams.delete(key);
                }
            });

            var price = priceValues();
            if (price.min > 0) {
                url.searchParams.set('min_price', String(price.min));
            } else {
                url.searchParams.delete('min_price');
            }
            if (price.max > 0 && price.max < priceMax) {
                url.searchParams.set('max_price', String(price.max));
            } else {
                url.searchParams.delete('max_price');
            }

            var keyword = $search.length ? $.trim($search.val()) : '';
            url.searchParams.delete('q');
            if (searchParam === 's') {
                url.searchParams.set('s', keyword);
                if (!url.searchParams.get('post_type')) {
                    url.searchParams.set('post_type', initialUrl.searchParams.get('post_type') || 'product');
                }
            } else {
                url.searchParams.delete('s');
                if (keyword) {
                    url.searchParams.set(searchParam, keyword);
                }
            }

            var sort = $root.find('.vf-ps-sort-select').val();
            if (sort && sort !== defaultOrder) {
                url.searchParams.set('orderby', sort);
            } else {
                url.searchParams.delete('orderby');
            }
            if (contextCat) {
                url.searchParams.set('category', String(contextCat));
            } else {
                url.searchParams.delete('category');
            }

            return url.toString();
        }

        function updateCount(total) {
            if (total === undefined || total === null || total === '') {
                return;
            }
            var count = parseInt(total, 10) || 0;
            $root.find('.vf-ps-count-num').text(count);
            $root.find('.vf-ps-count').prop('hidden', count < 1);
        }

        function renderChips() {
            var $chips = $root.find('.vf-ps-chips');
            $chips.empty();

            $root.find('.vf-ps-sidebar input:checked').each(function () {
                var label = $(this).data('label') || this.value;
                var $chip = $('<button type="button" class="vf-ps-chip"></button>');
                $chip.attr('data-name', this.name).attr('data-value', this.value).text(label + ' ×');
                $chips.append($chip);
            });

            var price = priceValues();
            if (price.min > 0 || (price.max > 0 && price.max < priceMax)) {
                var $priceChip = $('<button type="button" class="vf-ps-chip" data-chip="price"></button>');
                $priceChip.text(price.min + ' – ' + price.max + ' ×');
                $chips.append($priceChip);
            }

            var active = $chips.children().length;
            $chips.prop('hidden', active === 0);
            var $badge = $root.find('.vf-ps-filters-badge');
            $badge.text(active).prop('hidden', active === 0);
        }

        function syncDependentFilters() {
            var continents = checkedValues('continents');
            var leagues = checkedValues('leagues');

            $root.find('input[name="leagues"]').each(function () {
                var allowed = String($(this).data('continents') || '').split(',').filter(Boolean);
                var visible = !continents.length || allowed.some(function (item) {
                    return continents.indexOf(item) !== -1;
                });
                $(this).closest('.vf-ps-check').toggleClass('is-out', !visible);
                if (!visible) {
                    this.checked = false;
                }
            });

            $root.find('input[name="clubs"]').each(function () {
                var continent = String($(this).data('continent') || '');
                var league = String($(this).data('league') || '');
                var visible = (!continents.length || continents.indexOf(continent) !== -1) &&
                    (!leagues.length || leagues.indexOf(league) !== -1);
                $(this).closest('.vf-ps-check').toggleClass('is-out', !visible);
                if (!visible) {
                    this.checked = false;
                }
            });

            $root.find('input[name="nations"]').each(function () {
                var continent = String($(this).data('continent') || '');
                var visible = !continents.length || !continent || continents.indexOf(continent) !== -1;
                $(this).closest('.vf-ps-check').toggleClass('is-out', !visible);
                if (!visible) {
                    this.checked = false;
                }
            });
        }

        function load(page, push) {
            if (!window.vf_ps) {
                return;
            }

            if (request) {
                request.abort();
            }

            var price = priceValues();
            $results.addClass('is-loading');
            request = $.ajax({
                url: vf_ps.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'vf_product_results',
                    nonce: vf_ps.nonce,
                    q: $search.length ? $.trim($search.val()) : '',
                    continent: checkedValues('continents').join(','),
                    league: checkedValues('leagues').join(','),
                    club: checkedValues('clubs').join(','),
                    nation: checkedValues('nations').join(','),
                    legend: checkedValues('legends').join(','),
                    decade: checkedValues('decades').join(','),
                    more: checkedValues('more').join(','),
                    min_price: price.min > 0 ? price.min : '',
                    max_price: (price.max > 0 && price.max < priceMax) ? price.max : '',
                    orderby: $root.find('.vf-ps-sort-select').val() || defaultOrder,
                    context_cat: contextCat,
                    per_page: perPage,
                    columns: columns,
                    paginate: paginate,
                    empty: emptyText,
                    page: page || 1
                }
            });
            request.done(function (response) {
                if (!response || !response.success) {
                    return;
                }
                $results.html(response.data.html);
                updateCount(response.data.found);
                if (push && window.history && window.history.pushState) {
                    var url = new URL(buildUrl());
                    if (page > 1) {
                        url.searchParams.set('paged', String(page));
                    }
                    window.history.pushState({ vfPs: true }, '', url.toString());
                }
            });
            request.always(function (_, status) {
                if (status !== 'abort') {
                    $results.removeClass('is-loading');
                }
            });
        }

        function updateHero() {
            var value = $search.length ? $.trim($search.val()) : '';
            $root.find('.vf-ps-hero-term').text(value);
        }

        function refresh(page) {
            var nextPage = (typeof page === 'number' && isFinite(page)) ? page : 1;
            syncDependentFilters();
            renderChips();
            load(nextPage, true);
        }

        syncDependentFilters();
        renderChips();

        $root.on('change', '.vf-ps-sidebar input[type="checkbox"], .vf-ps-sort-select', function () {
            refresh(1);
        });

        $search.on('input', function () {
            var value = $.trim($search.val());
            $root.find('.vf-ps-search-clear').toggleClass('is-visible', value.length > 0);
            updateHero();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(refresh, 420);
        });

        $search.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(searchTimer);
                refresh();
            }
        });

        $root.on('click', '.vf-ps-search-clear', function () {
            $search.val('');
            $(this).removeClass('is-visible');
            updateHero();
            refresh();
        });

        function setPrice(min, max) {
            var span = priceMax || 1;
            var minPct;
            var maxPct;

            min = Math.max(0, Math.min(min, max));
            max = Math.min(priceMax, Math.max(max, min));
            $root.find('.vf-ps-range-min, .vf-ps-min').val(min);
            $root.find('.vf-ps-range-max, .vf-ps-max').val(max);

            minPct = (min / span) * 100;
            maxPct = (max / span) * 100;
            $root.find('.vf-ps-range').css({
                '--vf-ps-min-pct': minPct + '%',
                '--vf-ps-max-pct': maxPct + '%'
            });
        }

        setPrice(parseInt($root.find('.vf-ps-min').val(), 10) || 0, parseInt($root.find('.vf-ps-max').val(), 10) || priceMax);

        $root.on('input', '.vf-ps-range-min, .vf-ps-range-max', function () {
            setPrice(parseInt($root.find('.vf-ps-range-min').val(), 10) || 0, parseInt($root.find('.vf-ps-range-max').val(), 10) || 0);
            clearTimeout(priceTimer);
            priceTimer = setTimeout(refresh, 250);
        });

        $root.on('change', '.vf-ps-min, .vf-ps-max', function () {
            setPrice(parseInt($root.find('.vf-ps-min').val(), 10) || 0, parseInt($root.find('.vf-ps-max').val(), 10) || priceMax);
            refresh();
        });

        $root.on('click', '.vf-ps-chip', function () {
            var $chip = $(this);
            if ($chip.data('chip') === 'price') {
                setPrice(0, priceMax);
            } else {
                $root.find('input[name="' + $chip.attr('data-name') + '"][value="' + $chip.attr('data-value') + '"]').prop('checked', false);
            }
            refresh();
        });

        $root.on('click', '.vf-ps-clear, .vf-ps-empty-reset', function () {
            $root.find('.vf-ps-sidebar input[type="checkbox"]').prop('checked', false);
            $root.find('.vf-ps-list-search').val('');
            $root.find('.vf-ps-check').removeClass('is-search-miss');
            $search.val('');
            $root.find('.vf-ps-search-clear').removeClass('is-visible');
            $root.find('.vf-ps-sort-select').val(defaultOrder);
            setPrice(0, priceMax);
            updateHero();
            $root.removeClass('is-open');
            refresh();
        });

        $root.on('input', '.vf-ps-list-search', function () {
            var term = $.trim($(this).val()).toLowerCase();
            $(this).closest('.vf-ps-section-body').find('.vf-ps-check').each(function () {
                var label = $(this).find('.vf-ps-check-label').text().toLowerCase();
                $(this).toggleClass('is-search-miss', term.length > 0 && label.indexOf(term) === -1);
            });
        });

        $root.on('click', '.vf-ps-section-toggle', function () {
            var open = $(this).attr('aria-expanded') === 'true';
            $(this).attr('aria-expanded', open ? 'false' : 'true');
        });

        $root.on('click', '.vf-ps-filters-open', function () {
            $root.addClass('is-open');
        });
        $root.on('click', '.vf-ps-sidebar-close, .vf-ps-overlay', function () {
            $root.removeClass('is-open');
        });

        $results.on('click', '.vf-ps-page', function (event) {
            var page = parseInt($(this).attr('data-page'), 10);
            if (!page || $(this).hasClass('is-current')) {
                return;
            }
            event.preventDefault();
            refresh(page);
            var top = $root.offset().top - 80;
            $('html, body').animate({ scrollTop: Math.max(0, top) }, 200);
        });

        $(window).on('popstate.vfPs', function () {
            window.location.reload();
        });
    }

    $(function () {
        $('.elementor-widget-vf-product-search-filters').each(function () {
            init($(this));
        });
    });

    $(window).on('elementor/frontend/init', function () {
        if (!window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorFrontend.hooks.addAction('frontend/element_ready/vf-product-search-filters.default', init);
    });
})(jQuery);
