<?php
namespace VintageFootballElementorWidgets\Widgets\VFBreadcrumbs;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_VFBreadcrumbs extends Widget_Base {

    public function get_name() {
        return 'vf-breadcrumbs';
    }

    public function get_title() {
        return __( 'VF Breadcrumbs', 'xstore-child' );
    }

    public function get_icon() {
        return 'eicon-breadcrumbs';
    }

    public function get_categories() {
        return [ 'vintage-football' ];
    }

    public function get_keywords() {
        return [ 'breadcrumb', 'breadcrumbs', 'navigation', 'back', 'history', 'vintage', 'football', 'woocommerce', 'shop' ];
    }

    public function get_style_depends() {
        return [ 'vf-breadcrumbs-style' ];
    }

    protected function register_controls() {

        // =========================================================================
        // CONTENT SECTION: RETURN BUTTON
        // =========================================================================
        $this->start_controls_section(
            'section_return_button',
            [
                'label' => __( 'Return Button', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_return_button',
            [
                'label'        => __( 'Show Return Button', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'return_button_text',
            [
                'label'       => __( 'Button Text', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'RETURN TO PREVIOUS PAGE', 'xstore-child' ),
                'placeholder' => __( 'RETURN TO PREVIOUS PAGE', 'xstore-child' ),
                'condition'   => [
                    'show_return_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_return_icon',
            [
                'label'        => __( 'Show Chevron Icon', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'show_return_button' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // CONTENT SECTION: BREADCRUMBS TRAIL
        // =========================================================================
        $this->start_controls_section(
            'section_breadcrumbs',
            [
                'label' => __( 'Breadcrumbs Trail', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_home',
            [
                'label'        => __( 'Show Home', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'home_label',
            [
                'label'     => __( 'Home Label', 'xstore-child' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => __( 'HOME', 'xstore-child' ),
                'condition' => [
                    'show_home' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'include_shop',
            [
                'label'        => __( 'Include Shop Link', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'description'  => __( 'Include WooCommerce shop link before product categories.', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'show_current',
            [
                'label'        => __( 'Show Current Item', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'text_transform',
            [
                'label'   => __( 'Text Transform', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'uppercase',
                'options' => [
                    'uppercase'  => __( 'Uppercase', 'xstore-child' ),
                    'capitalize' => __( 'Capitalize', 'xstore-child' ),
                    'none'       => __( 'Normal (None)', 'xstore-child' ),
                ],
            ]
        );

        $this->add_control(
            'enable_schema',
            [
                'label'        => __( 'Enable Schema.org (JSON-LD)', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Output BreadcrumbList structured data for search engine SEO.', 'xstore-child' ),
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE SECTION: GENERAL CONTAINER
        // =========================================================================
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => __( 'Container', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => __( 'Padding', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-breadcrumbs-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_gap',
            [
                'label'      => __( 'Gap Between Left & Right', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-breadcrumbs-container' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE SECTION: RETURN BUTTON
        // =========================================================================
        $this->start_controls_section(
            'section_style_return_btn',
            [
                'label'     => __( 'Return Button', 'xstore-child' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_return_button' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'return_btn_typography',
                'selector' => '{{WRAPPER}} .vf-bc-return-btn',
            ]
        );

        $this->start_controls_tabs( 'tabs_return_btn' );

        $this->start_controls_tab(
            'tab_return_btn_normal',
            [
                'label' => __( 'Normal', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'return_btn_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-return-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'return_btn_icon_color',
            [
                'label'     => __( 'Icon Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-return-icon svg' => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_return_btn_hover',
            [
                'label' => __( 'Hover', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'return_btn_hover_color',
            [
                'label'     => __( 'Text Hover Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-return-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'return_btn_hover_icon_color',
            [
                'label'     => __( 'Icon Hover Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-return-btn:hover .vf-bc-return-icon svg' => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'return_btn_icon_size',
            [
                'label'      => __( 'Icon Size', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 8, 'max' => 24 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-bc-return-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );

        $this->add_responsive_control(
            'return_btn_icon_gap',
            [
                'label'      => __( 'Icon Gap', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 2, 'max' => 20 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-bc-return-btn' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE SECTION: BREADCRUMBS
        // =========================================================================
        $this->start_controls_section(
            'section_style_breadcrumbs',
            [
                'label' => __( 'Breadcrumbs', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'breadcrumbs_typography',
                'selector' => '{{WRAPPER}} .vf-bc-list',
            ]
        );

        $this->add_control(
            'link_color',
            [
                'label'     => __( 'Link Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_hover_color',
            [
                'label'     => __( 'Link Hover Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'current_color',
            [
                'label'     => __( 'Current Item Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-item.is-current .vf-bc-current-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'delimiter_color',
            [
                'label'     => __( 'Delimiter Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-delimiter' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'delimiter_spacing',
            [
                'label'      => __( 'Delimiter Spacing', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 2, 'max' => 24 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-bc-delimiter' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $settings['preview_fallback'] = 'yes';
        vf_render_breadcrumbs( $settings );
    }

    protected function content_template() {
        // Server-side rendered in PHP
    }
}

