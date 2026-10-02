/**
 * VF Product Categories Filters - Interactive Frontend with AJAX & Pagination
 */
(function ($) {
    'use strict';

    /**
     * Initialize VF Product Categories Filters Widget
     */
    function initVFProductCatFilters($scope) {
        var $wrapper = $scope.find('.vf-cat-filters-wrapper');
        if (!$wrapper.length) {
            $wrapper = $scope.hasClass('vf-cat-filters-wrapper') ? $scope : null;
        }
        if (!$wrapper || !$wrapper.length) return;

        // Prevent double initialization
        if ($wrapper.data('vf-filters-init')) return;
        $wrapper.data('vf-filters-init', true);

        var parentId = parseInt($wrapper.data('parent-id'), 10) || 0;
        var perPage = parseInt($wrapper.data('per-page'), 10) || 15;

        var $grid = $wrapper.find('.vf-cat-grid');
        var $paginationWrap = $wrapper.find('.vf-pagination-wrapper');
        var $counterText = $wrapper.find('.vf-counter-text');
        var $searchInput = $wrapper.find('.vf-search-input');
        var $searchClear = $wrapper.find('.vf-search-clear');
        var $sortSelect = $wrapper.find('.vf-sort-select');
        var $continentBtns = $wrapper.find('.vf-filter-group--continent .vf-filter-btn');
        var $leagueGroup = $wrapper.find('.vf-filter-group--league');
        var $leagueBtns = $leagueGroup.find('.vf-filter-btn');
        var $leagueSelect = $wrapper.find('.vf-filter-select:not(.vf-sort-select)');
        var $emptyState = $wrapper.find('.vf-empty-state');

        var activeContinent = 'all';
        var activeLeague = 'all';
        var activeSearch = '';
        var activeSort = $sortSelect.val() || 'name_asc';

        // Detect initial page from URL (/page/2/) or DOM data attribute
        var urlMatch = window.location.pathname.match(/\/page\/(\d+)\/?/i);
        var urlPage = urlMatch ? parseInt(urlMatch[1], 10) : 0;
        var currentPage = urlPage || parseInt($wrapper.data('current-page'), 10) || 1;

        var searchTimeout = null;
        var currentRequest = null;

        /**
         * Construct pagination URL (e.g. /clubs/page/2/)
         */
        function getPageUrl(pageNum) {
            var pathname = window.location.pathname;
            var cleanPath = pathname.replace(/\/page\/\d+\/?/i, '').replace(/\/+$/, '');
            var newPath = (pageNum > 1) ? (cleanPath + '/page/' + pageNum + '/') : (cleanPath + '/');
            return window.location.origin + newPath + window.location.search;
        }

        /**
         * Push URL to browser history
         */
        function updateBrowserUrl(pageNum) {
            if (!window.history || !window.history.pushState) return;
            var targetUrl = getPageUrl(pageNum);
            if (window.location.href !== targetUrl) {
                window.history.pushState({
                    page: pageNum,
                    continent: activeContinent,
                    league: activeLeague,
                    search: activeSearch,
                    sort: activeSort
                }, '', targetUrl);
            }
        }

        /**
         * Update league filter buttons based on selected continent
         */
        function updateLeaguePillsVisibility(selectedContinent) {
            if (!$leagueBtns.length) return;

            $leagueBtns.each(function () {
                var $btn = $(this);
                var btnLeague = $btn.data('league');
                var btnContinent = $btn.data('continent-parent');

                if (btnLeague === 'all') {
                    $btn.show();
                    return;
                }

                if (selectedContinent === 'all' || !btnContinent || btnContinent === selectedContinent) {
                    $btn.show();
                } else {
                    $btn.hide();
                    // If the hidden league was active, reset league to all
                    if ($btn.hasClass('is-active')) {
                        $btn.removeClass('is-active');
                        $leagueBtns.filter('[data-league="all"]').addClass('is-active');
                        activeLeague = 'all';
                    }
                }
            });
        }

        /**
         * Fetch categories via AJAX
         */
        function fetchCategories(scrollToTop) {
            if (typeof vf_ajax_object === 'undefined' || !vf_ajax_object.ajax_url) {
                return;
            }

            // Abort running request
            if (currentRequest && currentRequest.readyState !== 4) {
                currentRequest.abort();
            }

            $grid.addClass('is-loading');

            var postData = {
                action: 'vf_filter_product_categories',
                nonce: vf_ajax_object.nonce,
                parent_id: parentId,
                continent: activeContinent,
                league: activeLeague,
                search: activeSearch,
                sort: activeSort,
                page: currentPage,
                per_page: perPage,
                base_url: getPageUrl(currentPage)
            };

            currentRequest = $.ajax({
                url: vf_ajax_object.ajax_url,
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function (res) {
                    $grid.removeClass('is-loading');

                    if (res.success && res.data) {
                        var data = res.data;

                        // 1. Update Grid Cards
                        if (data.html && data.html.trim().length > 0) {
                            $grid.find('.vf-cat-card').remove();
                            $emptyState.removeClass('is-visible');
                            $grid.prepend(data.html);
                        } else {
                            $grid.find('.vf-cat-card').remove();
                            $emptyState.addClass('is-visible');
                        }

                        // 2. Update Pagination Navigation
                        if (data.pagination_html) {
                            $paginationWrap.html(data.pagination_html);
                        } else {
                            $paginationWrap.empty();
                        }

                        // 3. Update Result Counter Text
                        if (data.count_text && $counterText.length) {
                            $counterText.text(data.count_text);
                        }

                        // 4. Smooth scroll to top of categories if paged
                        if (scrollToTop) {
                            var offsetTop = $wrapper.offset().top - 80;
                            $('html, body').animate({ scrollTop: Math.max(0, offsetTop) }, 300);
                        }
                    }
                },
                error: function (xhr, status) {
                    if (status !== 'abort') {
                        $grid.removeClass('is-loading');
                    }
                }
            });
        }

        // ==========================================
        // Event Listeners
        // ==========================================

        // Continent Pill Click
        $continentBtns.on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var continent = $btn.data('continent') || 'all';

            $continentBtns.removeClass('is-active');
            $btn.addClass('is-active');

            activeContinent = continent;
            activeLeague = 'all';
            currentPage = 1;
            updateBrowserUrl(1);

            if ($leagueBtns.length) {
                $leagueBtns.removeClass('is-active');
                $leagueBtns.filter('[data-league="all"]').addClass('is-active');
                updateLeaguePillsVisibility(continent);
            }
            if ($leagueSelect.length) {
                $leagueSelect.val('all');
            }

            fetchCategories(false);
        });

        // League Pill Click
        $leagueBtns.on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var league = $btn.data('league') || 'all';

            $leagueBtns.removeClass('is-active');
            $btn.addClass('is-active');

            activeLeague = league;
            currentPage = 1;
            updateBrowserUrl(1);

            fetchCategories(false);
        });

        // League Select Dropdown
        $leagueSelect.on('change', function () {
            activeLeague = $(this).val() || 'all';
            currentPage = 1;
            updateBrowserUrl(1);
            fetchCategories(false);
        });

        // Sort By Select Dropdown
        $sortSelect.on('change', function () {
            activeSort = $(this).val() || 'name_asc';
            currentPage = 1;
            updateBrowserUrl(1);
            fetchCategories(false);
        });

        // Search Input (Instant with 320ms debounce)
        $searchInput.on('input keyup', function () {
            var val = $(this).val();
            activeSearch = val.trim();
            currentPage = 1;
            updateBrowserUrl(1);

            if (val.length > 0) {
                $searchClear.addClass('is-visible');
            } else {
                $searchClear.removeClass('is-visible');
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                fetchCategories(false);
            }, 320);
        });

        // Clear Search Button
        $searchClear.on('click', function () {
            $searchInput.val('');
            $searchClear.removeClass('is-visible');
            activeSearch = '';
            currentPage = 1;
            updateBrowserUrl(1);
            fetchCategories(false);
        });

        // Pagination Buttons Click (Delegated on pagination wrapper)
        $paginationWrap.on('click', '.vf-page-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            if ($btn.hasClass('is-active')) return;

            var pageNum = parseInt($btn.data('page'), 10);
            if (!pageNum || isNaN(pageNum)) return;

            currentPage = pageNum;
            updateBrowserUrl(currentPage);
            fetchCategories(true);
        });

        // Reset All Button (in empty state)
        $wrapper.on('click', '.vf-empty-reset-btn', function (e) {
            e.preventDefault();

            // Reset Search
            activeSearch = '';
            $searchInput.val('');
            $searchClear.removeClass('is-visible');

            // Reset Continent
            activeContinent = 'all';
            $continentBtns.removeClass('is-active');
            $continentBtns.filter('[data-continent="all"]').addClass('is-active');

            // Reset League
            activeLeague = 'all';
            if ($leagueBtns.length) {
                $leagueBtns.removeClass('is-active');
                $leagueBtns.filter('[data-league="all"]').addClass('is-active');
                updateLeaguePillsVisibility('all');
            }
            if ($leagueSelect.length) {
                $leagueSelect.val('all');
            }

            // Reset Sort
            activeSort = 'name_asc';
            $sortSelect.val('name_asc');

            currentPage = 1;
            updateBrowserUrl(1);
            fetchCategories(false);
        });

        // Handle Browser Back / Forward buttons (popstate)
        $(window).off('popstate.vfFilters').on('popstate.vfFilters', function (e) {
            var m = window.location.pathname.match(/\/page\/(\d+)\/?/i);
            var pageFromUrl = m ? parseInt(m[1], 10) : 1;

            if (e.originalEvent && e.originalEvent.state) {
                var state = e.originalEvent.state;
                if (state.page) pageFromUrl = state.page;
                if (state.continent !== undefined && state.continent !== activeContinent) {
                    activeContinent = state.continent;
                    $continentBtns.removeClass('is-active');
                    $continentBtns.filter('[data-continent="' + activeContinent + '"]').addClass('is-active');
                    updateLeaguePillsVisibility(activeContinent);
                }
                if (state.league !== undefined && state.league !== activeLeague) {
                    activeLeague = state.league;
                    if ($leagueBtns.length) {
                        $leagueBtns.removeClass('is-active');
                        $leagueBtns.filter('[data-league="' + activeLeague + '"]').addClass('is-active');
                    }
                    if ($leagueSelect.length) {
                        $leagueSelect.val(activeLeague);
                    }
                }
                if (state.search !== undefined && state.search !== activeSearch) {
                    activeSearch = state.search;
                    $searchInput.val(activeSearch);
                    if (activeSearch) $searchClear.addClass('is-visible');
                    else $searchClear.removeClass('is-visible');
                }
                if (state.sort !== undefined && state.sort !== activeSort) {
                    activeSort = state.sort;
                    $sortSelect.val(activeSort);
                }
            }

            if (pageFromUrl !== currentPage) {
                currentPage = pageFromUrl;
                fetchCategories(true);
            }
        });

        // Initial League Visibility setup
        updateLeaguePillsVisibility(activeContinent);
    }

    // Initialize on DOM Ready
    $(function () {
        $('.vf-cat-filters-wrapper').each(function () {
            initVFProductCatFilters($(this));
        });
    });

    // Hook into Elementor Frontend
    $(window).on('elementor/frontend/init', function () {
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            window.elementorFrontend.hooks.addAction(
                'frontend/element_ready/vf-product-categories-filters.default',
                function ($scope) {
                    initVFProductCatFilters($scope);
                }
            );
        }
    });

})(jQuery);
