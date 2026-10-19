<?php
namespace VintageFootballElementorWidgets\Widgets\VFProductSearchFilters;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_VFProductSearchFilters extends Widget_Base {

    public function get_name() {
        return 'vf-product-search-filters';
    }

    public function get_title() {
        return __( 'VF Product Search Filters', 'xstore-child' );
    }

    public function get_icon() {
        return 'eicon-filter';
    }

    public function get_categories() {
        return array( 'vintage-football' );
    }

    public function get_keywords() {
        return array( 'search', 'filter', 'products', 'shop', 'archive', 'vintage' );
    }

    public function get_style_depends() {
        return array( 'vf-product-search-filters-style' );
    }

    public function get_script_depends() {
        return array( 'vf-product-search-filters-script' );
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_toolbar',
            array(
                'label' => __( 'Toolbar', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'show_hero',
            array(
                'label'        => __( 'Search hero', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'xstore-child' ),
                'label_off'    => __( 'Hide', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'hero_label',
            array(
                'label'     => __( 'Hero label', 'xstore-child' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => __( 'Search results for:', 'xstore-child' ),
                'condition' => array( 'show_hero' => 'yes' ),
            )
        );

        $this->add_control(
            'show_search',
            array(
                'label'        => __( 'Search', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'search_placeholder',
            array(
                'label'     => __( 'Search placeholder', 'xstore-child' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => __( 'Search kits', 'xstore-child' ),
                'condition' => array( 'show_search' => 'yes' ),
            )
        );

        $this->add_control(
            'show_count',
            array(
                'label'        => __( 'Result count', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'count_label',
            array(
                'label'     => __( 'Count label', 'xstore-child' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => __( 'Products found', 'xstore-child' ),
                'condition' => array( 'show_count' => 'yes' ),
            )
        );

        $this->add_control(
            'show_sort',
            array(
                'label'        => __( 'Sort', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'default_orderby',
            array(
                'label'     => __( 'Default sort', 'xstore-child' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'popularity',
                'options'   => array(
                    'popularity' => __( 'Popularity', 'xstore-child' ),
                    'date'       => __( 'Date: Newest first', 'xstore-child' ),
                    'date-asc'   => __( 'Date: Oldest first', 'xstore-child' ),
                    'price'      => __( 'Price: Low to high', 'xstore-child' ),
                    'price-desc' => __( 'Price: High to low', 'xstore-child' ),
                    'title'      => __( 'Name: A to Z', 'xstore-child' ),
                    'title-desc' => __( 'Name: Z to A', 'xstore-child' ),
                ),
                'condition' => array( 'show_sort' => 'yes' ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_filters',
            array(
                'label' => __( 'Filters', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'sidebar_position',
            array(
                'label'   => __( 'Sidebar position', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => array(
                    'left'  => __( 'Left', 'xstore-child' ),
                    'right' => __( 'Right', 'xstore-child' ),
                ),
            )
        );

        $this->add_control(
            'sections_state',
            array(
                'label'   => __( 'Filter sections', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'closed',
                'options' => array(
                    'closed' => __( 'Closed', 'xstore-child' ),
                    'open'   => __( 'Open', 'xstore-child' ),
                ),
            )
        );

        $fields = array(
            'show_continent' => __( 'Continent', 'xstore-child' ),
            'show_league'    => __( 'League', 'xstore-child' ),
            'show_club'      => __( 'Club', 'xstore-child' ),
            'show_nation'    => __( 'National teams', 'xstore-child' ),
            'show_legend'    => __( 'Legend', 'xstore-child' ),
            'show_decade'    => __( 'Decades & Eras', 'xstore-child' ),
            'show_price'     => __( 'Price', 'xstore-child' ),
            'show_more'      => __( 'More categories', 'xstore-child' ),
        );

        foreach ( $fields as $key => $label ) {
            $this->add_control(
                $key,
                array(
                    'label'        => $label,
                    'type'         => Controls_Manager::SWITCHER,
                    'label_on'     => __( 'Show', 'xstore-child' ),
                    'label_off'    => __( 'Hide', 'xstore-child' ),
                    'return_value' => 'yes',
                    'default'      => 'yes',
                )
            );
        }

        $this->end_controls_section();

        $this->start_controls_section(
            'section_results',
            array(
                'label' => __( 'Product results', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'per_page',
            array(
                'label'   => __( 'Products per page', 'xstore-child' ),
                'type'    => Controls_Manager::NUMBER,
                'min'     => 1,
                'max'     => 48,
                'step'    => 1,
                'default' => 12,
            )
        );

        $this->add_responsive_control(
            'columns',
            array(
                'label'          => __( 'Columns', 'xstore-child' ),
                'type'           => Controls_Manager::NUMBER,
                'min'            => 1,
                'max'            => 6,
                'step'           => 1,
                'default'        => 3,
                'tablet_default' => 2,
                'mobile_default' => 2,
                'selectors'      => array(
                    '{{WRAPPER}} .vf-ps' => '--vf-ps-cols: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'show_pagination',
            array(
                'label'        => __( 'Pagination', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'empty_text',
            array(
                'label'   => __( 'Empty results text', 'xstore-child' ),
                'type'    => Controls_Manager::TEXT,
                'default' => __( 'No products found matching your search.', 'xstore-child' ),
            )
        );

        $this->add_control(
            'product_parts_heading',
            array(
                'label' => __( 'Product card parts', 'xstore-child' ),
                'type'  => Controls_Manager::HEADING,
            )
        );

        $parts = array(
            'show_image'  => __( 'Image', 'xstore-child' ),
            'show_title'  => __( 'Title', 'xstore-child' ),
            'show_price'  => __( 'Price', 'xstore-child' ),
            'show_rating' => __( 'Rating', 'xstore-child' ),
            'show_badge'  => __( 'Sale badge', 'xstore-child' ),
            'show_button' => __( 'Add to cart button', 'xstore-child' ),
        );

        foreach ( $parts as $key => $label ) {
            $this->add_control(
                $key,
                array(
                    'label'        => $label,
                    'type'         => Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default'      => 'yes',
                )
            );
        }

        $this->end_controls_section();

        $this->register_style_controls();
    }

    /**
     * Style controls apply only after a value is chosen. Defaults live in widget.css.
     */
    protected function register_style_controls() {
        $this->start_controls_section(
            'section_style_layout',
            array(
                'label' => __( 'Layout', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'sidebar_width',
            array(
                'label'      => __( 'Sidebar width', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array( 'min' => 180, 'max' => 420 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .vf-ps' => '--vf-ps-sidebar: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'column_gap',
            array(
                'label'      => __( 'Column gap', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array( 'min' => 0, 'max' => 80 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .vf-ps' => '--vf-ps-layout-gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'product_gap',
            array(
                'label'      => __( 'Product gap', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array( 'min' => 0, 'max' => 60 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .vf-ps' => '--vf-ps-gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_search',
            array(
                'label' => __( 'Search', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'search_typography',
                'selector' => '{{WRAPPER}} .vf-ps-search-input',
            )
        );

        $this->add_control(
            'search_color',
            array(
                'label'     => __( 'Text color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-search-input' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'search_background',
            array(
                'label'     => __( 'Background', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-search-input' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'search_radius',
            array(
                'label'      => __( 'Border radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array( 'min' => 0, 'max' => 40 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .vf-ps-search-input' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_count',
            array(
                'label' => __( 'Result count', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'count_number_color',
            array(
                'label'     => __( 'Number color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-count-num' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'count_number_typography',
                'selector' => '{{WRAPPER}} .vf-ps-count-num',
            )
        );

        $this->add_control(
            'count_label_color',
            array(
                'label'     => __( 'Label color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-count' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_sidebar',
            array(
                'label' => __( 'Sidebar', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'sidebar_background',
            array(
                'label'     => __( 'Background', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-sidebar' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'filter_heading_color',
            array(
                'label'     => __( 'Heading color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-section-toggle' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'filter_heading_typography',
                'selector' => '{{WRAPPER}} .vf-ps-section-toggle',
            )
        );

        $this->add_control(
            'filter_label_color',
            array(
                'label'     => __( 'Option color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-check' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'filter_count_color',
            array(
                'label'     => __( 'Option count color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-check-count' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_products',
            array(
                'label' => __( 'Products', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'card_background',
            array(
                'label'     => __( 'Card background', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-results ul.products li.product' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .vf-ps-results ul.products li.product',
            )
        );

        $this->add_responsive_control(
            'card_radius',
            array(
                'label'      => __( 'Card radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array( 'min' => 0, 'max' => 40 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .vf-ps-results ul.products li.product' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden;',
                ),
            )
        );

        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __( 'Card padding', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .vf-ps-results ul.products li.product' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .vf-ps-results ul.products li.product',
            )
        );

        $this->add_responsive_control(
            'image_radius',
            array(
                'label'      => __( 'Image radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array( 'min' => 0, 'max' => 40 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .vf-ps-results ul.products li.product img' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label'     => __( 'Title color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-results .woocommerce-loop-product__title, {{WRAPPER}} .vf-ps-results ul.products li.product .woocommerce-LoopProduct-link' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .vf-ps-results .woocommerce-loop-product__title',
            )
        );

        $this->add_control(
            'price_color',
            array(
                'label'     => __( 'Price color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-results ul.products li.product .price' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'price_typography',
                'selector' => '{{WRAPPER}} .vf-ps-results ul.products li.product .price',
            )
        );

        $this->add_control(
            'sale_price_color',
            array(
                'label'     => __( 'Sale price color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-results ul.products li.product .price ins' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_pagination',
            array(
                'label' => __( 'Pagination', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'page_color',
            array(
                'label'     => __( 'Text color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-page' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'page_active_color',
            array(
                'label'     => __( 'Active text color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-page.is-current' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'page_active_background',
            array(
                'label'     => __( 'Active background', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .vf-ps-page.is-current' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'page_radius',
            array(
                'label'      => __( 'Border radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array( 'min' => 0, 'max' => 30 ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .vf-ps-page' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $choices  = vf_ps_filter_choices();
        $active   = vf_ps_active_filters();
        if ( empty( $active['context_cat'] ) && function_exists( 'is_product_category' ) && is_product_category() ) {
            $active['context_cat'] = (int) get_queried_object_id();
        }
        if ( '' === $active['orderby'] ) {
            $active['orderby'] = ! empty( $settings['default_orderby'] ) ? $settings['default_orderby'] : 'popularity';
        }
        $per_page = isset( $settings['per_page'] ) ? (int) $settings['per_page'] : 12;
        $columns  = isset( $settings['columns'] ) ? (int) $settings['columns'] : 3;
        $keyword  = $active['q'];
        $max_base = vf_ps_catalog_max_price();
        $max_show = max( 1, (int) ceil( vf_ps_to_current_price( $max_base ) ) );
        $min_val  = $active['min'] ? (int) round( $active['min'] ) : 0;
        $max_val  = $active['max'] ? (int) round( $active['max'] ) : $max_show;
        $param    = ( is_search() || isset( $_GET['s'] ) ) ? 's' : 'q';
        $expanded = ( isset( $settings['sections_state'] ) && 'open' === $settings['sections_state'] ) ? 'true' : 'false';
        $paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

        ob_start();
        $found = vf_ps_render_loop(
            $active,
            $paged,
            array(
                'per_page' => $per_page,
                'columns'  => $columns,
                'paginate' => 'yes' === $settings['show_pagination'],
                'empty'    => $settings['empty_text'],
            )
        );
        $loop  = ob_get_clean();

        $orderby = $active['orderby'] ? $active['orderby'] : 'popularity';
        $sorts   = array(
            'popularity' => __( 'Popularity', 'xstore-child' ),
            'date'       => __( 'Date: Newest first', 'xstore-child' ),
            'date-asc'   => __( 'Date: Oldest first', 'xstore-child' ),
            'price'      => __( 'Price: Low to high', 'xstore-child' ),
            'price-desc' => __( 'Price: High to low', 'xstore-child' ),
            'title'      => __( 'Name: A to Z', 'xstore-child' ),
            'title-desc' => __( 'Name: Z to A', 'xstore-child' ),
        );
        ?>
        <?php
        $root_class = 'vf-ps';
        if ( 'right' === $settings['sidebar_position'] ) {
            $root_class .= ' is-sidebar-right';
        }
        $hide_map = array(
            'show_image'  => 'is-hide-image',
            'show_title'  => 'is-hide-title',
            'show_price'  => 'is-hide-price',
            'show_rating' => 'is-hide-rating',
            'show_badge'  => 'is-hide-badge',
            'show_button' => 'is-hide-button',
        );
        foreach ( $hide_map as $setting_key => $class_name ) {
            if ( 'yes' !== $settings[ $setting_key ] ) {
                $root_class .= ' ' . $class_name;
            }
        }
        ?>
        <div
            class="<?php echo esc_attr( $root_class ); ?>"
            data-search-param="<?php echo esc_attr( $param ); ?>"
            data-price-max="<?php echo esc_attr( $max_show ); ?>"
            data-context-cat="<?php echo esc_attr( $active['context_cat'] ); ?>"
            data-per-page="<?php echo esc_attr( $per_page ); ?>"
            data-columns="<?php echo esc_attr( $columns ); ?>"
            data-paginate="<?php echo esc_attr( $settings['show_pagination'] ); ?>"
            data-empty="<?php echo esc_attr( $settings['empty_text'] ); ?>"
            data-orderby="<?php echo esc_attr( $orderby ); ?>"
            data-found="<?php echo esc_attr( $found ); ?>">

            <?php if ( 'yes' === $settings['show_hero'] ) : ?>
            <div class="vf-ps-hero">
                <h1 class="vf-ps-hero-title">
                    <?php echo esc_html( $settings['hero_label'] ); ?>
                    <span class="vf-ps-hero-term"><?php echo esc_html( $keyword ); ?></span>
                </h1>
            </div>
            <?php endif; ?>

            <div class="vf-ps-toolbar">
                <?php if ( 'yes' === $settings['show_search'] ) : ?>
                <div class="vf-ps-search">
                    <svg class="vf-ps-search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <input type="search" class="vf-ps-search-input" value="<?php echo esc_attr( $keyword ); ?>" placeholder="<?php echo esc_attr( $settings['search_placeholder'] ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Search products', 'xstore-child' ); ?>" />
                    <button type="button" class="vf-ps-search-clear<?php echo $keyword ? ' is-visible' : ''; ?>" aria-label="<?php esc_attr_e( 'Clear search', 'xstore-child' ); ?>">&times;</button>
                </div>
                <?php endif; ?>

                <?php if ( 'yes' === $settings['show_count'] ) : ?>
                <p class="vf-ps-count" aria-live="polite"<?php echo $found ? '' : ' hidden'; ?>>
                    <strong class="vf-ps-count-num"><?php echo esc_html( (string) $found ); ?></strong>
                    <?php echo esc_html( $settings['count_label'] ); ?>
                </p>
                <?php endif; ?>

                <button type="button" class="vf-ps-filters-open">
                    <?php esc_html_e( 'Filters', 'xstore-child' ); ?>
                    <span class="vf-ps-filters-badge" hidden>0</span>
                </button>

                <?php if ( 'yes' === $settings['show_sort'] ) : ?>
                <label class="vf-ps-sort">
                    <span><?php esc_html_e( 'Sort by', 'xstore-child' ); ?></span>
                    <select class="vf-ps-sort-select" aria-label="<?php esc_attr_e( 'Sort products', 'xstore-child' ); ?>">
                        <?php foreach ( $sorts as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $orderby, $value ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
            </div>

            <div class="vf-ps-chips" hidden></div>

            <div class="vf-ps-layout">
                <div class="vf-ps-overlay" hidden></div>
                <aside class="vf-ps-sidebar" aria-label="<?php esc_attr_e( 'Product filters', 'xstore-child' ); ?>">
                    <div class="vf-ps-sidebar-head">
                        <strong><?php esc_html_e( 'Filters', 'xstore-child' ); ?></strong>
                        <button type="button" class="vf-ps-clear"><?php esc_html_e( 'Clear all', 'xstore-child' ); ?></button>
                        <button type="button" class="vf-ps-sidebar-close" aria-label="<?php esc_attr_e( 'Close filters', 'xstore-child' ); ?>">&times;</button>
                    </div>

                    <?php if ( 'yes' === $settings['show_continent'] && $choices['continents'] ) : ?>
                        <section class="vf-ps-section">
                            <button type="button" class="vf-ps-section-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>"><span><?php esc_html_e( 'Continents', 'xstore-child' ); ?></span></button>
                            <div class="vf-ps-section-body">
                                <?php foreach ( $choices['continents'] as $slug => $count ) : ?>
                                    <?php vf_ps_render_checkbox( 'continents', $slug, vf_ps_continent_label( $slug ), $count, in_array( $slug, $active['continent'], true ) ); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( 'yes' === $settings['show_league'] && $choices['leagues'] ) : ?>
                        <section class="vf-ps-section">
                            <button type="button" class="vf-ps-section-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>"><span><?php esc_html_e( 'Leagues', 'xstore-child' ); ?></span></button>
                            <div class="vf-ps-section-body">
                                <?php foreach ( $choices['leagues'] as $slug => $league ) : ?>
                                    <?php
                                    vf_ps_render_checkbox(
                                        'leagues',
                                        $slug,
                                        vf_ps_league_label( $slug ),
                                        $league['count'],
                                        in_array( $slug, $active['league'], true ),
                                        array( 'data-continents' => implode( ',', array_keys( $league['continents'] ) ) )
                                    );
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( 'yes' === $settings['show_club'] && $choices['clubs'] ) : ?>
                        <section class="vf-ps-section">
                            <button type="button" class="vf-ps-section-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>"><span><?php esc_html_e( 'Clubs', 'xstore-child' ); ?></span></button>
                            <div class="vf-ps-section-body">
                                <input type="search" class="vf-ps-list-search" placeholder="<?php esc_attr_e( 'Find a club', 'xstore-child' ); ?>" aria-label="<?php esc_attr_e( 'Find a club', 'xstore-child' ); ?>" />
                                <div class="vf-ps-checks">
                                    <?php foreach ( $choices['clubs'] as $club ) : ?>
                                        <?php
                                        vf_ps_render_checkbox(
                                            'clubs',
                                            (string) $club->term_id,
                                            $club->name,
                                            (int) $club->count,
                                            in_array( (int) $club->term_id, $active['club'], true ),
                                            array(
                                                'data-continent' => vf_ps_term_meta_value( $club->term_id, 'prod_cat_continent' ),
                                                'data-league'    => vf_ps_term_meta_value( $club->term_id, 'prod_cat_league' ),
                                            )
                                        );
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( 'yes' === $settings['show_nation'] && ! empty( $choices['nations'] ) ) : ?>
                        <section class="vf-ps-section">
                            <button type="button" class="vf-ps-section-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>"><span><?php esc_html_e( 'National teams', 'xstore-child' ); ?></span></button>
                            <div class="vf-ps-section-body">
                                <input type="search" class="vf-ps-list-search" placeholder="<?php esc_attr_e( 'Find a nation', 'xstore-child' ); ?>" aria-label="<?php esc_attr_e( 'Find a nation', 'xstore-child' ); ?>" />
                                <div class="vf-ps-checks">
                                    <?php foreach ( $choices['nations'] as $nation ) : ?>
                                        <?php
                                        vf_ps_render_checkbox(
                                            'nations',
                                            $nation->slug,
                                            $nation->name,
                                            (int) $nation->count,
                                            in_array( $nation->slug, $active['nation'], true ),
                                            array(
                                                'data-continent' => vf_ps_term_meta_value( $nation->term_id, 'prod_cat_continent' ),
                                            )
                                        );
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( 'yes' === $settings['show_legend'] && $choices['legends'] ) : ?>
                        <section class="vf-ps-section">
                            <button type="button" class="vf-ps-section-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>"><span><?php esc_html_e( 'Legends', 'xstore-child' ); ?></span></button>
                            <div class="vf-ps-section-body">
                                <input type="search" class="vf-ps-list-search" placeholder="<?php esc_attr_e( 'Find a legend', 'xstore-child' ); ?>" aria-label="<?php esc_attr_e( 'Find a legend', 'xstore-child' ); ?>" />
                                <div class="vf-ps-checks">
                                    <?php foreach ( $choices['legends'] as $legend ) : ?>
                                        <?php vf_ps_render_checkbox( 'legends', $legend->slug, $legend->name, (int) $legend->count, in_array( $legend->slug, $active['legend'], true ) ); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( 'yes' === $settings['show_decade'] ) : ?>
                        <section class="vf-ps-section">
                            <button type="button" class="vf-ps-section-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>"><span><?php esc_html_e( 'Decades & Eras', 'xstore-child' ); ?></span></button>
                            <div class="vf-ps-section-body vf-ps-pills">
                                <?php foreach ( vf_ps_decade_options() as $year => $label ) : ?>
                                    <?php vf_ps_render_checkbox( 'decades', (string) $year, $label, 0, in_array( (string) $year, $active['decade'], true ) ); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( 'yes' === $settings['show_price'] ) : ?>
                        <?php $currency_symbol = vf_ps_currency_symbol(); ?>
                        <section class="vf-ps-section">
                            <button type="button" class="vf-ps-section-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>"><span><?php esc_html_e( 'Prices', 'xstore-child' ); ?></span></button>
                            <div class="vf-ps-section-body">
                                <div class="vf-ps-range">
                                    <input type="range" class="vf-ps-range-min" min="0" max="<?php echo esc_attr( $max_show ); ?>" value="<?php echo esc_attr( $min_val ); ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'xstore-child' ); ?>" />
                                    <input type="range" class="vf-ps-range-max" min="0" max="<?php echo esc_attr( $max_show ); ?>" value="<?php echo esc_attr( $max_val ); ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'xstore-child' ); ?>" />
                                </div>
                                <div class="vf-ps-price-fields">
                                    <label>
                                        <span class="vf-ps-price-label"><?php esc_html_e( 'Min', 'xstore-child' ); ?></span>
                                        <span class="vf-ps-price-input">
                                            <input type="number" class="vf-ps-min" min="0" max="<?php echo esc_attr( $max_show ); ?>" value="<?php echo esc_attr( $min_val ); ?>" />
                                            <?php if ( $currency_symbol ) : ?>
                                                <span class="vf-ps-currency" aria-hidden="true"><?php echo esc_html( $currency_symbol ); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                    <label>
                                        <span class="vf-ps-price-label"><?php esc_html_e( 'Max', 'xstore-child' ); ?></span>
                                        <span class="vf-ps-price-input">
                                            <input type="number" class="vf-ps-max" min="0" max="<?php echo esc_attr( $max_show ); ?>" value="<?php echo esc_attr( $max_val ); ?>" />
                                            <?php if ( $currency_symbol ) : ?>
                                                <span class="vf-ps-currency" aria-hidden="true"><?php echo esc_html( $currency_symbol ); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ( 'yes' === $settings['show_more'] && ! empty( $choices['more'] ) ) : ?>
                        <section class="vf-ps-section">
                            <button type="button" class="vf-ps-section-toggle" aria-expanded="<?php echo esc_attr( $expanded ); ?>"><span><?php esc_html_e( 'More categories', 'xstore-child' ); ?></span></button>
                            <div class="vf-ps-section-body">
                                <div class="vf-ps-checks">
                                    <?php foreach ( $choices['more'] as $term ) : ?>
                                        <?php vf_ps_render_checkbox( 'more', $term->slug, $term->name, (int) $term->count, in_array( $term->slug, $active['more'], true ) ); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </aside>

                <div class="vf-ps-results" aria-live="polite"><?php echo $loop; ?></div>
            </div>
        </div>
        <?php
    }
}
