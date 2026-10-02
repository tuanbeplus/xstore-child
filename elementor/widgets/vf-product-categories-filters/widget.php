<?php
namespace VintageFootballElementorWidgets\Widgets\VFProductCategoriesFilters;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_VFProductCategoriesFilters extends Widget_Base {

    public function get_name() {
        return 'vf-product-categories-filters';
    }

    public function get_title() {
        return __( 'VF Product Categories Filters', 'xstore-child' );
    }

    public function get_icon() {
        return 'eicon-filter';
    }

    public function get_categories() {
        return [ 'vintage-football' ];
    }

    public function get_keywords() {
        return [ 'product', 'category', 'filter', 'football', 'club', 'national', 'teams', 'vintage' ];
    }

    public function get_style_depends() {
        return [ 'vf-cat-filters-style' ];
    }

    public function get_script_depends() {
        return [ 'vf-cat-filters-script' ];
    }

    /**
     * Get parent category choices for dropdown control
     */
    private function get_parent_category_options() {
        $options = [ '' => __( '— Select Category —', 'xstore-child' ) ];

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return $options;
        }

        $terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $options[ $term->term_id ] = $term->name . ' (ID: ' . $term->term_id . ')';
            }
        }

        return $options;
    }

    /**
     * Map continent slugs to friendly labels
     */
    private function get_continent_label( $slug ) {
        $labels = [
            'europe'        => __( 'Europe', 'xstore-child' ),
            'south_america' => __( 'South America', 'xstore-child' ),
            'north_america' => __( 'North America', 'xstore-child' ),
            'asia'          => __( 'Asia', 'xstore-child' ),
            'africa'        => __( 'Africa', 'xstore-child' ),
            'oceania'       => __( 'Oceania', 'xstore-child' ),
        ];

        return isset( $labels[ $slug ] ) ? $labels[ $slug ] : ucwords( str_replace( [ '_', '-' ], ' ', $slug ) );
    }

    /**
     * Map league slugs to friendly labels
     */
    private function get_league_label( $slug ) {
        $labels = [
            'premier_league'             => __( 'Premier League', 'xstore-child' ),
            'la_liga'                    => __( 'La Liga', 'xstore-child' ),
            'serie_a'                    => __( 'Serie A', 'xstore-child' ),
            'bundesliga'                 => __( 'Bundesliga', 'xstore-child' ),
            'ligue_1'                    => __( 'Ligue 1', 'xstore-child' ),
            'primeira_liga'              => __( 'Primeira Liga', 'xstore-child' ),
            'eredivisie'                 => __( 'Eredivisie', 'xstore-child' ),
            'scottish_premiership'       => __( 'Scottish Premiership', 'xstore-child' ),
            'brasileirao_serie_a'        => __( 'Brasileirão Série A', 'xstore-child' ),
            'argentina_primera_division' => __( 'Argentine Primera División', 'xstore-child' ),
            'liga_mx'                    => __( 'Liga MX', 'xstore-child' ),
            'chile_primera_division'     => __( 'Chile Primera División', 'xstore-child' ),
            'paraguay_primera_division'  => __( 'Paraguay Primera División', 'xstore-child' ),
        ];

        return isset( $labels[ $slug ] ) ? $labels[ $slug ] : ucwords( str_replace( [ '_', '-' ], ' ', $slug ) );
    }

    /**
     * Register controls
     */
    protected function register_controls() {

        // =========================================================================
        // CONTENT TAB
        // =========================================================================

        // 1. Query Section
        $this->start_controls_section(
            'section_query',
            [
                'label' => __( 'Query Settings', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'query_source',
            [
                'label'       => __( 'Category Source', 'xstore-child' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'current',
                'options'     => [
                    'current' => __( 'Current Category (Automatic)', 'xstore-child' ),
                    'custom'  => __( 'Specific Parent Category', 'xstore-child' ),
                ],
                'description' => __( 'On category templates (Clubs, National Teams, Legend), "Automatic" dynamically queries child categories.', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'parent_category',
            [
                'label'       => __( 'Parent Category', 'xstore-child' ),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_parent_category_options(),
                'default'     => '',
                'condition'   => [
                    'query_source' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'preview_category',
            [
                'label'       => __( 'Preview Category in Editor', 'xstore-child' ),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_parent_category_options(),
                'default'     => '',
                'condition'   => [
                    'query_source' => 'current',
                ],
                'description' => __( 'Select a parent category to preview live data in the Elementor template editor.', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'include_descendants',
            [
                'label'        => __( 'Include Sub-Children (All Descendants)', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'If "No", only direct children of the parent category are loaded.', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'hide_empty',
            [
                'label'        => __( 'Hide Empty Categories', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => __( 'Order By', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'name',
                'options' => [
                    'name'       => __( 'Name (Alphabetical)', 'xstore-child' ),
                    'count'      => __( 'Product Count', 'xstore-child' ),
                    'term_id'    => __( 'Category ID', 'xstore-child' ),
                    'slug'       => __( 'Slug', 'xstore-child' ),
                    'menu_order' => __( 'Menu Order', 'xstore-child' ),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label'   => __( 'Order Direction', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => [
                    'ASC'  => __( 'Ascending (A-Z / Low-High)', 'xstore-child' ),
                    'DESC' => __( 'Descending (Z-A / High-Low)', 'xstore-child' ),
                ],
            ]
        );

        $this->add_control(
            'exclude_terms',
            [
                'label'       => __( 'Exclude Category IDs', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => '1520, 1530',
                'description' => __( 'Comma-separated IDs of child categories to exclude.', 'xstore-child' ),
            ]
        );

        $this->end_controls_section();

        // 2. Filter Bar Section
        $this->start_controls_section(
            'section_filters',
            [
                'label' => __( 'Filters & Search', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label'        => __( 'Enable Search Input', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'search_placeholder',
            [
                'label'       => __( 'Search Placeholder', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Search teams...', 'xstore-child' ),
                'condition'   => [
                    'show_search' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_continent_filter',
            [
                'label'        => __( 'Enable Continent Pills', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Automatically hides if no child categories have continent data (e.g. Legends).', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'show_league_filter',
            [
                'label'        => __( 'Enable League Filter', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Automatically hides if no child categories have league data (e.g. National Teams, Legends).', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'league_filter_type',
            [
                'label'       => __( 'League Filter Style', 'xstore-child' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'pills',
                'options'     => [
                    'pills'    => __( 'Sub-Pill Row', 'xstore-child' ),
                    'dropdown' => __( 'Dropdown Select', 'xstore-child' ),
                ],
                'condition'   => [
                    'show_league_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'all_filter_label',
            [
                'label'   => __( '"All" Button Label', 'xstore-child' ),
                'type'    => Controls_Manager::TEXT,
                'default' => __( 'All', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'all_leagues_label',
            [
                'label'     => __( '"All Leagues" Label', 'xstore-child' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => __( 'All Leagues', 'xstore-child' ),
                'condition' => [
                    'show_league_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'filter_layout',
            [
                'label'   => __( 'Filter Bar Layout', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'space-between',
                'options' => [
                    'space-between' => __( 'Pills Left, Search Right', 'xstore-child' ),
                    'search-first'  => __( 'Search Left, Pills Right', 'xstore-child' ),
                    'stacked'       => __( 'Stacked (Pills & Search Full Width)', 'xstore-child' ),
                ],
            ]
        );

        $this->add_control(
            'show_counter',
            [
                'label'        => __( 'Show Results Counter', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $this->add_control(
            'empty_results_text',
            [
                'label'   => __( 'Empty State Text', 'xstore-child' ),
                'type'    => Controls_Manager::TEXT,
                'default' => __( 'No teams found matching your filter.', 'xstore-child' ),
            ]
        );

        $this->end_controls_section();

        // 3. Card Settings
        $this->start_controls_section(
            'section_cards',
            [
                'label' => __( 'Category Cards', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_product_count',
            [
                'label'        => __( 'Show Product Count', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'count_text_plural',
            [
                'label'       => __( 'Count Text (Plural)', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( '{count} products', 'xstore-child' ),
                'description' => __( 'Use {count} for the number of products.', 'xstore-child' ),
                'condition'   => [
                    'show_product_count' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'count_text_singular',
            [
                'label'       => __( 'Count Text (Singular)', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( '{count} product', 'xstore-child' ),
                'condition'   => [
                    'show_product_count' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'fallback_image',
            [
                'label'       => __( 'Fallback Category Image', 'xstore-child' ),
                'type'        => Controls_Manager::MEDIA,
                'default'     => [],
                'description' => __( 'Used if a category has no thumbnail uploaded.', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'open_new_tab',
            [
                'label'        => __( 'Open in New Tab', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE TAB
        // =========================================================================

        // Style: Filter Bar & Search
        $this->start_controls_section(
            'section_style_filter_bar',
            [
                'label' => __( 'Filter Bar & Search', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'filter_bar_spacing',
            [
                'label'      => __( 'Bottom Spacing', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 80 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-filter-bar' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'search_box_width',
            [
                'label'      => __( 'Search Box Width', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [
                    'px' => [ 'min' => 180, 'max' => 500 ],
                    '%'  => [ 'min' => 10, 'max' => 100 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-filter-search' => 'max-width: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'search_text_color',
            [
                'label'     => __( 'Search Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-search-input' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'search_bg_color',
            [
                'label'     => __( 'Search Background', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-search-input' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'search_border_color',
            [
                'label'     => __( 'Search Border Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-search-input' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'search_icon_color',
            [
                'label'     => __( 'Search Icon Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-search-icon' => 'stroke: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_section();

        // Style: Filter Pills
        $this->start_controls_section(
            'section_style_pills',
            [
                'label' => __( 'Filter Pills', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'pills_typography',
                'selector' => '{{WRAPPER}} .vf-filter-btn',
            ]
        );

        $this->add_responsive_control(
            'pills_padding',
            [
                'label'      => __( 'Padding', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-filter-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pills_border_radius',
            [
                'label'      => __( 'Border Radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 999 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-filter-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_pill_states' );

        // Normal Pill State
        $this->start_controls_tab(
            'tab_pill_normal',
            [
                'label' => __( 'Normal', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'pill_text_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-filter-btn' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'pill_bg_color',
            [
                'label'     => __( 'Background Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-filter-btn' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'pill_border_color',
            [
                'label'     => __( 'Border Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-filter-btn' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        // Active / Hover Pill State
        $this->start_controls_tab(
            'tab_pill_active',
            [
                'label' => __( 'Active / Hover', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'pill_active_text_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-filter-btn:hover, {{WRAPPER}} .vf-filter-btn.is-active' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'pill_active_bg_color',
            [
                'label'     => __( 'Background Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-filter-btn:hover, {{WRAPPER}} .vf-filter-btn.is-active' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'pill_active_border_color',
            [
                'label'     => __( 'Border Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-filter-btn:hover, {{WRAPPER}} .vf-filter-btn.is-active' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // Style: Grid Layout
        $this->start_controls_section(
            'section_style_grid',
            [
                'label' => __( 'Grid Layout', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'grid_columns',
            [
                'label'          => __( 'Columns', 'xstore-child' ),
                'type'           => Controls_Manager::SELECT,
                'options'        => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'selectors'      => [
                    '{{WRAPPER}} .vf-cat-grid' => '--vf-grid-cols: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label'      => __( 'Grid Gap', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [
                    'px' => [ 'min' => 4, 'max' => 50 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-cat-grid' => '--vf-grid-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style: Category Cards
        $this->start_controls_section(
            'section_style_cards',
            [
                'label' => __( 'Cards & Images', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_aspect_ratio',
            [
                'label'     => __( 'Card Aspect Ratio', 'xstore-child' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    '1 / 1'  => __( 'Square (1:1)', 'xstore-child' ),
                    '4 / 3'  => __( 'Landscape (4:3)', 'xstore-child' ),
                    '3 / 4'  => __( 'Portrait (3:4)', 'xstore-child' ),
                    '16 / 9' => __( 'Widescreen (16:9)', 'xstore-child' ),
                    'auto'   => __( 'Custom Height', 'xstore-child' ),
                ],
                'selectors' => [
                    '{{WRAPPER}} .vf-cat-card' => '--vf-card-aspect-ratio: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_min_height',
            [
                'label'      => __( 'Custom Height', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [ 'min' => 120, 'max' => 500 ],
                ],
                'condition'  => [
                    'card_aspect_ratio' => 'auto',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-cat-card' => '--vf-card-min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_border_radius',
            [
                'label'      => __( 'Border Radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 40 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-cat-card' => '--vf-card-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .vf-cat-card',
            ]
        );

        $this->end_controls_section();

        // Style: Typography & Colors
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => __( 'Card Typography', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'heading_title_style',
            [
                'label'     => __( 'Category / Team Name', 'xstore-child' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .vf-card-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __( 'Title Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-card-title' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'heading_count_style',
            [
                'label'     => __( 'Product Count', 'xstore-child' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'count_typography',
                'selector' => '{{WRAPPER}} .vf-card-count',
            ]
        );

        $this->add_control(
            'count_color',
            [
                'label'     => __( 'Count Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-card-count' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget frontend output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div style="padding:20px;background:#f8d7da;color:#721c24;border-radius:8px;">';
                esc_html_e( 'WooCommerce product categories taxonomy is not available.', 'xstore-child' );
                echo '</div>';
            }
            return;
        }

        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
        $parent_id = 0;

        // 1. Determine Parent Category ID
        if ( ! empty( $settings['query_source'] ) && 'custom' === $settings['query_source'] && ! empty( $settings['parent_category'] ) ) {
            $parent_id = absint( $settings['parent_category'] );
        } else {
            // Automatic detection
            $queried_obj = get_queried_object();
            if ( $queried_obj instanceof \WP_Term && 'product_cat' === $queried_obj->taxonomy ) {
                $parent_id = $queried_obj->term_id;
            } elseif ( $is_editor && ! empty( $settings['preview_category'] ) ) {
                $parent_id = absint( $settings['preview_category'] );
            } elseif ( $queried_obj instanceof \WP_Post ) {
                $matched_term = get_term_by( 'slug', $queried_obj->post_name, 'product_cat' );
                if ( $matched_term && ! is_wp_error( $matched_term ) ) {
                    $parent_id = $matched_term->term_id;
                }
            }

            // Fallback: check URL slug (e.g. /clubs/ or /national-teams/ or /clubs/page/2/)
            if ( 0 === $parent_id && ! empty( $_SERVER['REQUEST_URI'] ) ) {
                $uri_path   = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
                $clean_path = trim( preg_replace( '#/page/\d+/?#', '', $uri_path ), '/' );
                $segments   = explode( '/', $clean_path );
                $slug_cand  = end( $segments );
                if ( ! empty( $slug_cand ) ) {
                    $matched_term = get_term_by( 'slug', sanitize_title( $slug_cand ), 'product_cat' );
                    if ( $matched_term && ! is_wp_error( $matched_term ) ) {
                        $parent_id = $matched_term->term_id;
                    }
                }
            }
        }

        // Editor fallback: if parent_id is 0 in editor, find first top-level category with children (e.g. Clubs 1569)
        if ( 0 === $parent_id && $is_editor ) {
            $candidate_parents = [ 1569, 1570 ];
            foreach ( $candidate_parents as $cand_id ) {
                $cand = get_term( $cand_id, 'product_cat' );
                if ( $cand && ! is_wp_error( $cand ) ) {
                    $parent_id = $cand_id;
                    break;
                }
            }

            if ( 0 === $parent_id ) {
                // Find any category that has children
                $all_parents = get_terms( [
                    'taxonomy'   => 'product_cat',
                    'parent'     => 0,
                    'hide_empty' => false,
                ] );
                if ( ! is_wp_error( $all_parents ) && ! empty( $all_parents ) ) {
                    foreach ( $all_parents as $pt ) {
                        $test_children = get_term_children( $pt->term_id, 'product_cat' );
                        if ( ! empty( $test_children ) ) {
                            $parent_id = $pt->term_id;
                            break;
                        }
                    }
                }
            }
        }

        if ( 0 === $parent_id ) {
            if ( $is_editor ) {
                echo '<div style="padding:24px;background:#f9f9f9;border:1.5px dashed #ccc;border-radius:12px;text-align:center;color:#666;">';
                echo '<p style="margin:0 0 8px 0;font-weight:700;color:#111;">' . esc_html__( 'VF Product Categories Filters', 'xstore-child' ) . '</p>';
                echo '<p style="margin:0;">' . esc_html__( 'Please select a Preview Category in widget settings or assign this template to a parent category archive.', 'xstore-child' ) . '</p>';
                echo '</div>';
            }
            return;
        }

        // 2. Query Child Categories
        $query_args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => ( 'yes' === $settings['hide_empty'] ),
            'orderby'    => ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'name',
            'order'      => ! empty( $settings['order'] ) ? $settings['order'] : 'ASC',
        ];

        if ( 'yes' === $settings['include_descendants'] ) {
            $query_args['child_of'] = $parent_id;
        } else {
            $query_args['parent'] = $parent_id;
        }

        if ( ! empty( $settings['exclude_terms'] ) ) {
            $excludes = array_map( 'absint', explode( ',', $settings['exclude_terms'] ) );
            $query_args['exclude'] = $excludes;
        }

        $child_terms = get_terms( $query_args );

        if ( is_wp_error( $child_terms ) || empty( $child_terms ) ) {
            if ( $is_editor ) {
                echo '<div style="padding:24px;background:#f9f9f9;border:1.5px dashed #ccc;border-radius:12px;text-align:center;color:#666;">';
                echo '<p style="margin:0;">' . esc_html__( 'No child categories found for this parent category.', 'xstore-child' ) . '</p>';
                echo '</div>';
            }
            return;
        }

        // 3. Process Child Terms & Collect Continents and Leagues
        $categories_data = [];
        $unique_continents = [];
        $unique_leagues    = [];
        $has_continent_data = false;
        $has_league_data    = false;

        $fallback_img_url = '';
        if ( ! empty( $settings['fallback_image']['id'] ) ) {
            $fallback_img_url = wp_get_attachment_image_url( $settings['fallback_image']['id'], 'medium_large' );
        } elseif ( ! empty( $settings['fallback_image']['url'] ) ) {
            $fallback_img_url = $settings['fallback_image']['url'];
        }

        foreach ( $child_terms as $term ) {
            // Retrieve ACF / Meta
            $continent = '';
            $league    = '';

            if ( function_exists( 'get_field' ) ) {
                $continent = get_field( 'prod_cat_continent', 'product_cat_' . $term->term_id );
                $league    = get_field( 'prod_cat_league', 'product_cat_' . $term->term_id );
            }
            if ( empty( $continent ) ) {
                $continent = get_term_meta( $term->term_id, 'prod_cat_continent', true );
            }
            if ( empty( $league ) ) {
                $league = get_term_meta( $term->term_id, 'prod_cat_league', true );
            }

            // Fallback field names if ever customized
            if ( empty( $continent ) ) {
                $continent = get_term_meta( $term->term_id, 'continent', true );
            }
            if ( empty( $league ) ) {
                $league = get_term_meta( $term->term_id, 'league', true );
            }

            $continent = is_string( $continent ) ? trim( $continent ) : '';
            $league    = is_string( $league ) ? trim( $league ) : '';

            if ( ! empty( $continent ) ) {
                $has_continent_data = true;
                if ( ! isset( $unique_continents[ $continent ] ) ) {
                    $unique_continents[ $continent ] = $this->get_continent_label( $continent );
                }
            }

            if ( ! empty( $league ) ) {
                $has_league_data = true;
                if ( ! isset( $unique_leagues[ $league ] ) ) {
                    $unique_leagues[ $league ] = [
                        'label'     => $this->get_league_label( $league ),
                        'continent' => $continent,
                    ];
                }
            }

            // Thumbnail
            $thumb_id  = get_term_meta( $term->term_id, 'thumbnail_id', true );
            $image_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : $fallback_img_url;

            // Link
            $term_link = get_term_link( $term );
            if ( is_wp_error( $term_link ) ) {
                $term_link = '#';
            }

            $categories_data[] = [
                'id'        => $term->term_id,
                'name'      => $term->name,
                'slug'      => $term->slug,
                'count'     => $term->count,
                'link'      => $term_link,
                'image_url' => $image_url,
                'continent' => $continent,
                'league'    => $league,
            ];
        }

        // Determine if filters should be displayed
        // If categories have no continent/league data (e.g. Legends), auto-hide those filter pills!
        $show_continent_pills = ( 'yes' === $settings['show_continent_filter'] && $has_continent_data && ! empty( $unique_continents ) );
        $show_league_filter   = ( 'yes' === $settings['show_league_filter'] && $has_league_data && ! empty( $unique_leagues ) );
        $show_search          = ( 'yes' === $settings['show_search'] );

        $per_page         = 15;
        $total_categories = count( $categories_data );
        $total_pages      = max( 1, (int) ceil( $total_categories / $per_page ) );

        // Detect current page from query vars or request URI for SSR
        $current_page = 1;
        if ( get_query_var( 'paged' ) ) {
            $current_page = absint( get_query_var( 'paged' ) );
        } elseif ( get_query_var( 'page' ) ) {
            $current_page = absint( get_query_var( 'page' ) );
        } elseif ( ! empty( $_GET['paged'] ) ) {
            $current_page = absint( $_GET['paged'] );
        } elseif ( ! empty( $_SERVER['REQUEST_URI'] ) && preg_match( '#/page/([0-9]+)/?#', $_SERVER['REQUEST_URI'], $page_matches ) ) {
            $current_page = absint( $page_matches[1] );
        }
        $current_page = max( 1, min( $total_pages, $current_page ) );

        $offset        = ( $current_page - 1 ) * $per_page;
        $paged_initial = array_slice( $categories_data, $offset, $per_page );
        $initial_count = count( $paged_initial );

        if ( $total_categories === 0 ) {
            $count_text = __( 'Showing 0 categories', 'xstore-child' );
        } elseif ( $total_categories <= $per_page ) {
            $count_text = sprintf(
                /* translators: %d: total categories */
                __( 'Showing %d of %d categories', 'xstore-child' ),
                $total_categories,
                $total_categories
            );
        } else {
            $start_num  = $offset + 1;
            $end_num    = $offset + $initial_count;
            $count_text = sprintf(
                /* translators: 1: start count, 2: end count, 3: total categories */
                __( 'Showing %1$d–%2$d of %3$d categories', 'xstore-child' ),
                $start_num,
                $end_num,
                $total_categories
            );
        }
        ?>

        <div class="vf-cat-filters-wrapper" data-parent-id="<?php echo esc_attr( $parent_id ); ?>" data-per-page="<?php echo esc_attr( $per_page ); ?>" data-current-page="<?php echo esc_attr( $current_page ); ?>">

            <?php if ( $show_continent_pills || $show_search ) : ?>
                <div class="vf-filter-bar">

                    <div class="vf-filter-bar-left">
                        <?php if ( $show_continent_pills ) : ?>
                            <div class="vf-filter-group vf-filter-group--continent" role="tablist">
                                <button type="button" class="vf-filter-btn is-active" data-continent="all" role="tab">
                                    <?php echo esc_html( $settings['all_filter_label'] ); ?>
                                </button>
                                <?php foreach ( $unique_continents as $c_slug => $c_label ) : ?>
                                    <button type="button" class="vf-filter-btn" data-continent="<?php echo esc_attr( $c_slug ); ?>" role="tab">
                                        <?php echo esc_html( $c_label ); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="vf-filter-bar-right">
                        <!-- Sort by name and product count -->
                        <div class="vf-filter-sort-wrapper">
                            <select class="vf-filter-select vf-sort-select" aria-label="<?php esc_attr_e( 'Sort by', 'xstore-child' ); ?>">
                                <option value="name_asc"><?php esc_html_e( 'Name: A–Z', 'xstore-child' ); ?></option>
                                <option value="name_desc"><?php esc_html_e( 'Name: Z–A', 'xstore-child' ); ?></option>
                                <option value="count_desc"><?php esc_html_e( 'Products: High–Low', 'xstore-child' ); ?></option>
                                <option value="count_asc"><?php esc_html_e( 'Products: Low–High', 'xstore-child' ); ?></option>
                            </select>
                        </div>

                        <?php if ( $show_search ) : ?>
                            <div class="vf-filter-search">
                                <svg class="vf-search-icon" viewBox="0 0 24 24">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input
                                    type="text"
                                    class="vf-search-input"
                                    placeholder="<?php echo esc_attr( $settings['search_placeholder'] ); ?>"
                                    aria-label="<?php echo esc_attr( $settings['search_placeholder'] ); ?>"
                                    autocomplete="off"
                                />
                                <button type="button" class="vf-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'xstore-child' ); ?>">✕</button>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endif; ?>

            <?php if ( $show_continent_pills && $show_league_filter ) : ?>
                <!-- 1px Gray Divider between Continent and League -->
                <div class="vf-filter-divider"></div>
            <?php endif; ?>

            <?php
            // League Filter (if enabled and leagues exist)
            if ( $show_league_filter ) :
                if ( 'dropdown' === $settings['league_filter_type'] ) :
                    ?>
                    <div class="vf-filter-bar" style="margin-top: 0; margin-bottom: 20px;">
                        <div class="vf-filter-dropdown-wrapper">
                            <select class="vf-filter-select" aria-label="<?php echo esc_attr( $settings['all_leagues_label'] ); ?>">
                                <option value="all"><?php echo esc_html( $settings['all_leagues_label'] ); ?></option>
                                <?php foreach ( $unique_leagues as $l_slug => $l_data ) : ?>
                                    <option value="<?php echo esc_attr( $l_slug ); ?>"><?php echo esc_html( $l_data['label'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="vf-filter-group vf-filter-group--sub vf-filter-group--league" role="tablist">
                        <button type="button" class="vf-filter-btn is-active" data-league="all" role="tab">
                            <?php echo esc_html( $settings['all_leagues_label'] ); ?>
                        </button>
                        <?php foreach ( $unique_leagues as $l_slug => $l_data ) : ?>
                            <button
                                type="button"
                                class="vf-filter-btn"
                                data-league="<?php echo esc_attr( $l_slug ); ?>"
                                data-continent-parent="<?php echo esc_attr( $l_data['continent'] ); ?>"
                                role="tab">
                                <?php echo esc_html( $l_data['label'] ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php
                endif;
            endif;
            ?>

            <!-- Results Counter (categories shown per total) -->
            <div class="vf-filter-counter">
                <span class="vf-counter-text"><?php echo esc_html( $count_text ); ?></span>
            </div>

            <!-- Category Cards Grid (Page 1) -->
            <div class="vf-cat-grid">
                <?php if ( ! empty( $paged_initial ) ) : ?>
                    <?php foreach ( $paged_initial as $cat ) : ?>
                        <?php echo vf_render_single_category_card( $cat, $settings ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Empty State Message -->
                <div class="vf-empty-state <?php echo empty( $categories_data ) ? 'is-visible' : ''; ?>">
                    <svg class="vf-empty-icon" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                    <h4 class="vf-empty-title"><?php esc_html_e( 'No Teams Found', 'xstore-child' ); ?></h4>
                    <p class="vf-empty-text"><?php echo esc_html( $settings['empty_results_text'] ); ?></p>
                    <button type="button" class="vf-empty-reset-btn">
                        <?php esc_html_e( 'Reset Filters', 'xstore-child' ); ?>
                    </button>
                </div>
            </div>

            <!-- Pagination Navigation (Bottom) -->
            <div class="vf-pagination-wrapper">
                <?php echo vf_render_pagination_nav( $current_page, $total_pages ); ?>
            </div>

        </div>

        <?php
    }

    protected function content_template() {
        // Rendered server-side via PHP in Elementor editor
    }
}
