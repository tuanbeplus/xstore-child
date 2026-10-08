<?php
namespace VintageFootballElementorWidgets\Widgets\VFHeroSlider;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Icons_Manager;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Jetpack Site Accelerator (Image CDN) rewrites every <img> found in the_content
 * (Elementor output included) to i0.wp.com/...?resize=W,H, clamping the width to the
 * theme's global $content_width (XStore = 1170). The only supported opt-out is this
 * filter, so skip our slider images (marked with .vf-hero-img).
 */
add_filter(
    'jetpack_photon_skip_image',
    function ( $skip, $src, $tag ) {
        if ( is_string( $tag ) && false !== strpos( $tag, 'vf-hero-img' ) ) {
            return true;
        }
        return $skip;
    },
    10,
    3
);

class Widget_VFHeroSlider extends Widget_Base {

    public function get_name() {
        return 'vf-hero-slider';
    }

    public function get_title() {
        return __( 'VF Hero Slider', 'xstore-child' );
    }

    public function get_icon() {
        return 'eicon-slider-push';
    }

    public function get_categories() {
        return [ 'vintage-football' ];
    }

    public function get_keywords() {
        return [ 'hero', 'slider', 'banner', 'carousel', 'vintage', 'football', 'slides' ];
    }

    public function get_style_depends() {
        return [ 'vf-hero-slider-style' ];
    }

    public function get_script_depends() {
        return [ 'vf-hero-slider-script' ];
    }

    protected function register_controls() {

        // =========================================================================
        // CONTENT TAB - SLIDES
        // =========================================================================
        $this->start_controls_section(
            'section_slides',
            [
                'label' => __( 'Slides', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->start_controls_tabs( 'tabs_slide_item' );

        // TAB: Content
        $repeater->start_controls_tab(
            'tab_slide_content',
            [
                'label' => __( 'Content', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'badge_text',
            [
                'label'       => __( 'Badge Text', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => __( 'e.g. NEW ARRIVAL or BUY 2 GET 1 FREE', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'badge_icon',
            [
                'label'                  => __( 'Badge Icon', 'xstore-child' ),
                'type'                   => Controls_Manager::ICONS,
                'skin'                   => 'inline',
                'label_block'            => false,
                'exclude_inline_options' => [ 'svg' ],
            ]
        );

        $repeater->add_control(
            'title',
            [
                'label'       => __( 'Title', 'xstore-child' ),
                'type'        => Controls_Manager::TEXTAREA,
                'rows'        => 3,
                'default'     => __( "WEAR THE COLOURS.<br>RELIVE THE GLORY.", 'xstore-child' ),
                'placeholder' => __( 'Slide Headline (HTML br/span supported)', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'title_tag',
            [
                'label'   => __( 'Title Tag', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'h2',
                'options' => [
                    'h1'   => 'H1',
                    'h2'   => 'H2',
                    'h3'   => 'H3',
                    'h4'   => 'H4',
                    'span' => 'SPAN',
                    'div'  => 'DIV',
                ],
            ]
        );

        $repeater->add_control(
            'description',
            [
                'label'       => __( 'Description', 'xstore-child' ),
                'type'        => Controls_Manager::TEXTAREA,
                'rows'        => 4,
                'default'     => __( "Rediscover iconic club shirts from football's greatest eras, from unforgettable title wins to legendary European nights.", 'xstore-child' ),
                'placeholder' => __( 'Slide description text', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'primary_btn_text',
            [
                'label'       => __( 'Primary Button Text', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'SHOP CLUB SHIRTS', 'xstore-child' ),
                'placeholder' => __( 'Button Label', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'primary_btn_link',
            [
                'label'       => __( 'Primary Button Link', 'xstore-child' ),
                'type'        => Controls_Manager::URL,
                'placeholder' => __( 'https://vintagefootball.shop/clubs/', 'xstore-child' ),
                'default'     => [
                    'url'         => 'https://vintagefootball.shop/clubs/',
                    'is_external' => false,
                    'nofollow'    => false,
                ],
            ]
        );

        $repeater->add_control(
            'secondary_btn_text',
            [
                'label'       => __( 'Secondary Button Text', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => __( 'e.g. VIEW LEGENDS', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'secondary_btn_link',
            [
                'label'       => __( 'Secondary Button Link', 'xstore-child' ),
                'type'        => Controls_Manager::URL,
                'placeholder' => __( 'https://vintagefootball.shop/legends/', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'slide_link',
            [
                'label'       => __( 'Full Slide Link (Optional)', 'xstore-child' ),
                'type'        => Controls_Manager::URL,
                'description' => __( 'Makes entire slide background clickable (buttons will remain individually clickable).', 'xstore-child' ),
            ]
        );

        $repeater->end_controls_tab();

        // TAB: Media
        $repeater->start_controls_tab(
            'tab_slide_media',
            [
                'label' => __( 'Media', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'image_desktop',
            [
                'label'   => __( 'Desktop Image', 'xstore-child' ),
                'type'    => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $repeater->add_control(
            'image_tablet',
            [
                'label'       => __( 'Tablet Image (Optional)', 'xstore-child' ),
                'type'        => Controls_Manager::MEDIA,
                'description' => __( 'Optional. Falls back to desktop image if left empty.', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'image_mobile',
            [
                'label'       => __( 'Mobile Image (Optional)', 'xstore-child' ),
                'type'        => Controls_Manager::MEDIA,
                'description' => __( 'Optional. Optimized portrait/square image for smartphones.', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'image_alt',
            [
                'label'       => __( 'Custom Image Alt', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __( 'Leave empty for auto fallback', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'slide_bg_color',
            [
                'label'     => __( 'Slide Background Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} {{CURRENT_ITEM}}' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $repeater->end_controls_tab();

        // TAB: Customize
        $repeater->start_controls_tab(
            'tab_slide_custom',
            [
                'label' => __( 'Customize', 'xstore-child' ),
            ]
        );

        $repeater->add_control(
            'content_align_override',
            [
                'label'   => __( 'Horizontal Alignment', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    ''       => __( 'Default (from Layout)', 'xstore-child' ),
                    'left'   => __( 'Left', 'xstore-child' ),
                    'center' => __( 'Center', 'xstore-child' ),
                    'right'  => __( 'Right', 'xstore-child' ),
                ],
            ]
        );

        $repeater->add_control(
            'content_v_align_override',
            [
                'label'   => __( 'Vertical Alignment', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    ''       => __( 'Default (from Layout)', 'xstore-child' ),
                    'top'    => __( 'Top', 'xstore-child' ),
                    'middle' => __( 'Middle', 'xstore-child' ),
                    'bottom' => __( 'Bottom', 'xstore-child' ),
                ],
            ]
        );

        $repeater->add_control(
            'overlay_override',
            [
                'label'        => __( 'Custom Overlay for this Slide', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $repeater->add_control(
            'overlay_color_override',
            [
                'label'     => __( 'Overlay Color / Gradient', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'overlay_override' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} {{CURRENT_ITEM}} .vf-hero-overlay' => 'background: {{VALUE}} !important;',
                ],
            ]
        );

        $repeater->end_controls_tab();
        $repeater->end_controls_tabs();

        // Add Slides Repeater Control
        $this->add_control(
            'slides',
            [
                'label'       => __( 'Slide Items', 'xstore-child' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'title_field' => '{{{ title.replace(/<[^>]*>?/gm, "").substring(0, 30) || "Slide Item" }}}',
                'default'     => [
                    [
                        'badge_text'         => 'RETRO COLLECTION',
                        'title'              => "WEAR THE COLOURS.<br>RELIVE THE GLORY.",
                        'title_tag'          => 'h2',
                        'description'        => "Rediscover iconic club shirts from football's greatest eras, from unforgettable title wins to legendary European nights.",
                        'primary_btn_text'   => 'SHOP CLUB SHIRTS',
                        'primary_btn_link'   => [ 'url' => 'https://vintagefootball.shop/clubs/' ],
                        'secondary_btn_text' => 'EXPLORE LEGENDS',
                        'secondary_btn_link' => [ 'url' => 'https://vintagefootball.shop/legends/' ],
                    ],
                    [
                        'badge_text'         => 'WORLD CUP & EUROS',
                        'title'              => "GOLDEN ERAS.<br>NATIONAL PRIDE.",
                        'title_tag'          => 'h2',
                        'description'        => "Relive timeless international tournaments with authentic Brazil, Argentina, France, and England vintage jerseys.",
                        'primary_btn_text'   => 'SHOP NATIONAL TEAMS',
                        'primary_btn_link'   => [ 'url' => 'https://vintagefootball.shop/national-teams/' ],
                        'secondary_btn_text' => '',
                    ],
                    [
                        'badge_text'         => 'BUY 2 GET 1 FREE',
                        'title'              => "LEGENDS OF THE GAME.<br>FOREVER IMMORTAL.",
                        'title_tag'          => 'h2',
                        'description'        => "From Maradona and Zidane to Ronaldo and Messi - wear the shirts of the icons that shaped football history.",
                        'primary_btn_text'   => 'VIEW ALL LEGENDS',
                        'primary_btn_link'   => [ 'url' => 'https://vintagefootball.shop/legends/' ],
                        'secondary_btn_text' => 'VIEW DEALS',
                        'secondary_btn_link' => [ 'url' => 'https://vintagefootball.shop/shop/' ],
                    ],
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // CONTENT TAB - LAYOUT
        // =========================================================================
        $this->start_controls_section(
            'section_layout',
            [
                'label' => __( 'Layout & Dimensions', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'slider_height_mode',
            [
                'label'   => __( 'Height Mode', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'aspect',
                'options' => [
                    'aspect'   => __( 'Aspect Ratio (Recommended - Zero CLS)', 'xstore-child' ),
                    'fixed'    => __( 'Custom Height (px / vh)', 'xstore-child' ),
                    'viewport' => __( 'Full Viewport (100vh)', 'xstore-child' ),
                ],
            ]
        );

        $this->add_responsive_control(
            'aspect_ratio',
            [
                'label'       => __( 'Aspect Ratio (Width / Height)', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => '1024 / 359',
                'condition'   => [
                    'slider_height_mode' => 'aspect',
                ],
                'selectors'   => [
                    '{{WRAPPER}} .vf-hero-slider' => 'aspect-ratio: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'custom_slider_height',
            [
                'label'      => __( 'Custom Height', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'vh' ],
                'range'      => [
                    'px' => [ 'min' => 200, 'max' => 1000 ],
                    'vh' => [ 'min' => 20, 'max' => 100 ],
                ],
                'condition'  => [
                    'slider_height_mode' => 'fixed',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-slider' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_max_width',
            [
                'label'      => __( 'Content Max Width', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [
                    'px' => [ 'min' => 280, 'max' => 1000 ],
                    '%'  => [ 'min' => 20, 'max' => 100 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-content' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_align',
            [
                'label'     => __( 'Content Alignment', 'xstore-child' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => __( 'Left', 'xstore-child' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'xstore-child' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => __( 'Right', 'xstore-child' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-content'           => 'text-align: {{VALUE}};',
                    '{{WRAPPER}} .vf-hero-slide'             => 'justify-content: {{VALUE}};',
                    '{{WRAPPER}} .vf-hero-content-container' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_v_align',
            [
                'label'     => __( 'Vertical Alignment', 'xstore-child' ),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start' => [
                        'title' => __( 'Top', 'xstore-child' ),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'center'     => [
                        'title' => __( 'Middle', 'xstore-child' ),
                        'icon'  => 'eicon-v-align-middle',
                    ],
                    'flex-end'   => [
                        'title' => __( 'Bottom', 'xstore-child' ),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-slide'             => 'align-items: {{VALUE}};',
                    '{{WRAPPER}} .vf-hero-content-container' => 'align-items: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_object_position',
            [
                'label'     => __( 'Image Focal Point', 'xstore-child' ),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    'center center' => __( 'Center Center', 'xstore-child' ),
                    'center top'    => __( 'Center Top', 'xstore-child' ),
                    'center bottom' => __( 'Center Bottom', 'xstore-child' ),
                    'left center'   => __( 'Left Center', 'xstore-child' ),
                    'right center'  => __( 'Right Center', 'xstore-child' ),
                ],
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-media img' => 'object-position: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name'    => 'image',
                'default' => 'full',
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // CONTENT TAB - SLIDER SETTINGS
        // =========================================================================
        $this->start_controls_section(
            'section_slider_settings',
            [
                'label' => __( 'Slider Settings', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label'        => __( 'Autoplay', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label'     => __( 'Autoplay Speed (ms)', 'xstore-child' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 5000,
                'min'       => 1000,
                'max'       => 20000,
                'step'      => 500,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'pause_on_hover',
            [
                'label'        => __( 'Pause on Hover', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'pause_on_interaction',
            [
                'label'        => __( 'Pause on Interaction', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'condition'    => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'infinite',
            [
                'label'        => __( 'Infinite Loop', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'transition_effect',
            [
                'label'   => __( 'Transition Effect', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'fade',
                'options' => [
                    'fade'  => __( 'Crossfade (Recommended - Lightweight)', 'xstore-child' ),
                    'slide' => __( 'Slide Horizontal', 'xstore-child' ),
                ],
            ]
        );

        $this->add_control(
            'transition_speed',
            [
                'label'   => __( 'Transition Duration (ms)', 'xstore-child' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 700,
                'min'     => 200,
                'max'     => 3000,
                'step'    => 50,
            ]
        );

        $this->add_control(
            'swipe',
            [
                'label'        => __( 'Touch Swipe / Drag', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'keyboard',
            [
                'label'        => __( 'Keyboard Navigation (Arrows)', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_arrows',
            [
                'label'        => __( 'Show Navigation Arrows', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'arrows_hide_mobile',
            [
                'label'        => __( 'Hide Arrows on Mobile', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_dots',
            [
                'label'        => __( 'Show Navigation Dots', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'dots_position',
            [
                'label'     => __( 'Dots Position', 'xstore-child' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'bottom-left',
                'options'   => [
                    'bottom-left'   => __( 'Bottom Left', 'xstore-child' ),
                    'bottom-center' => __( 'Bottom Center', 'xstore-child' ),
                    'bottom-right'  => __( 'Bottom Right', 'xstore-child' ),
                ],
                'condition' => [
                    'show_dots' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_progress',
            [
                'label'        => __( 'Show Autoplay Progress Bar', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'condition'    => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_counter',
            [
                'label'        => __( 'Show Slide Counter (e.g. 01 / 03)', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $this->add_control(
            'preload_first_image',
            [
                'label'        => __( 'Preload / High Priority First Slide', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Adds fetchpriority="high" to slide 1 for faster LCP scores.', 'xstore-child' ),
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // CONTENT TAB - ANIMATION
        // =========================================================================
        $this->start_controls_section(
            'section_animation',
            [
                'label' => __( 'Animation & Effects', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'content_animation',
            [
                'label'   => __( 'Content Animation', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'fade-in-up',
                'options' => [
                    'fade-in-up' => __( 'Fade In Up (Smooth GPU)', 'xstore-child' ),
                    'fade-in'    => __( 'Fade In Only', 'xstore-child' ),
                    'none'       => __( 'None', 'xstore-child' ),
                ],
            ]
        );

        $this->add_control(
            'content_animation_duration',
            [
                'label'       => __( 'Content Animation Duration (ms)', 'xstore-child' ),
                'type'        => Controls_Manager::NUMBER,
                'placeholder' => 700,
                'min'         => 200,
                'max'         => 2000,
                'step'        => 50,
                'condition'   => [
                    'content_animation!' => 'none',
                ],
                'selectors'   => [
                    '{{WRAPPER}} .vf-hero-slider' => '--vf-hero-content-duration: {{VALUE}}ms;',
                ],
            ]
        );

        $this->add_control(
            'content_stagger',
            [
                'label'       => __( 'Content Stagger Delay (ms)', 'xstore-child' ),
                'type'        => Controls_Manager::NUMBER,
                'placeholder' => 120,
                'min'         => 0,
                'max'         => 500,
                'step'        => 10,
                'description' => __( 'Sequential delay between Badge -> Title -> Desc -> Buttons.', 'xstore-child' ),
                'condition'   => [
                    'content_animation!' => 'none',
                ],
                'selectors'   => [
                    '{{WRAPPER}} .vf-hero-slider' => '--vf-hero-stagger: {{VALUE}}ms;',
                ],
            ]
        );

        $this->add_control(
            'image_animation',
            [
                'label'   => __( 'Image Animation', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'fade-in',
                'options' => [
                    'fade-in' => __( 'Fade In (Smooth)', 'xstore-child' ),
                    'none'    => __( 'None', 'xstore-child' ),
                ],
            ]
        );

        $this->add_control(
            'image_animation_duration',
            [
                'label'       => __( 'Image Animation Duration (ms)', 'xstore-child' ),
                'type'        => Controls_Manager::NUMBER,
                'placeholder' => 900,
                'min'         => 200,
                'max'         => 3000,
                'step'        => 50,
                'condition'   => [
                    'image_animation!' => 'none',
                ],
                'selectors'   => [
                    '{{WRAPPER}} .vf-hero-slider' => '--vf-hero-img-duration: {{VALUE}}ms;',
                ],
            ]
        );

        $this->add_control(
            'image_zoom',
            [
                'label'        => __( 'Subtle Ken Burns Zoom Effect', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'description'  => __( 'Gentle continuous GPU scale effect while slide is active.', 'xstore-child' ),
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE TAB - OVERLAY & CONTAINER
        // =========================================================================
        $this->start_controls_section(
            'section_style_slider',
            [
                'label' => __( 'Container & Overlay', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'slider_border_radius',
            [
                'label'      => __( 'Border Radius', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-slider' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'slider_box_shadow',
                'selector' => '{{WRAPPER}} .vf-hero-slider',
            ]
        );

        $this->add_control(
            'overlay_heading',
            [
                'label'     => __( 'Slide Overlay', 'xstore-child' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'overlay_bg',
                'label'    => __( 'Overlay Background', 'xstore-child' ),
                'types'    => [ 'classic', 'gradient' ],
                'selector' => '{{WRAPPER}} .vf-hero-overlay',
            ]
        );

        $this->add_responsive_control(
            'overlay_opacity',
            [
                'label'     => __( 'Overlay Opacity', 'xstore-child' ),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-overlay' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'slide_padding',
            [
                'label'      => __( 'Slide Padding', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-slide' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE TAB - BADGE
        // =========================================================================
        $this->start_controls_section(
            'section_style_badge',
            [
                'label' => __( 'Badge', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'badge_typography',
                'selector' => '{{WRAPPER}} .vf-hero-badge',
            ]
        );

        $this->add_control(
            'badge_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'badge_bg_color',
            [
                'label'     => __( 'Background Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'badge_padding',
            [
                'label'      => __( 'Padding', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'badge_radius',
            [
                'label'      => __( 'Border Radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-badge' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'badge_margin_bottom',
            [
                'label'      => __( 'Bottom Spacing', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-badge' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE TAB - TITLE
        // =========================================================================
        $this->start_controls_section(
            'section_style_title',
            [
                'label' => __( 'Title', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .vf-hero-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Text_Shadow::get_type(),
            [
                'name'     => 'title_shadow',
                'selector' => '{{WRAPPER}} .vf-hero-title',
            ]
        );

        $this->add_responsive_control(
            'title_margin_bottom',
            [
                'label'      => __( 'Bottom Spacing', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE TAB - DESCRIPTION
        // =========================================================================
        $this->start_controls_section(
            'section_style_description',
            [
                'label' => __( 'Description', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'description_typography',
                'selector' => '{{WRAPPER}} .vf-hero-desc',
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-desc' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'description_margin_bottom',
            [
                'label'      => __( 'Bottom Spacing', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-desc' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE TAB - PRIMARY BUTTON
        // =========================================================================
        $this->start_controls_section(
            'section_style_primary_btn',
            [
                'label' => __( 'Primary Button', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'primary_btn_typography',
                'selector' => '{{WRAPPER}} .vf-hero-btn--primary',
            ]
        );

        $this->start_controls_tabs( 'tabs_primary_btn_style' );

        $this->start_controls_tab(
            'tab_primary_btn_normal',
            [ 'label' => __( 'Normal', 'xstore-child' ) ]
        );

        $this->add_control(
            'primary_btn_text_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--primary' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'primary_btn_bg_color',
            [
                'label'     => __( 'Background Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--primary' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_primary_btn_hover',
            [ 'label' => __( 'Hover', 'xstore-child' ) ]
        );

        $this->add_control(
            'primary_btn_text_color_hover',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--primary:hover' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'primary_btn_bg_color_hover',
            [
                'label'     => __( 'Background Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--primary:hover' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'primary_btn_padding',
            [
                'label'      => __( 'Padding', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-btn--primary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );

        $this->add_responsive_control(
            'primary_btn_radius',
            [
                'label'      => __( 'Border Radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-btn--primary' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE TAB - SECONDARY BUTTON
        // =========================================================================
        $this->start_controls_section(
            'section_style_secondary_btn',
            [
                'label' => __( 'Secondary Button', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'secondary_btn_typography',
                'selector' => '{{WRAPPER}} .vf-hero-btn--secondary',
            ]
        );

        $this->start_controls_tabs( 'tabs_secondary_btn_style' );

        $this->start_controls_tab(
            'tab_secondary_btn_normal',
            [ 'label' => __( 'Normal', 'xstore-child' ) ]
        );

        $this->add_control(
            'secondary_btn_text_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--secondary' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'secondary_btn_bg_color',
            [
                'label'     => __( 'Background Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--secondary' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'secondary_btn_border_color',
            [
                'label'     => __( 'Border Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--secondary' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_secondary_btn_hover',
            [ 'label' => __( 'Hover', 'xstore-child' ) ]
        );

        $this->add_control(
            'secondary_btn_text_color_hover',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--secondary:hover' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'secondary_btn_bg_color_hover',
            [
                'label'     => __( 'Background Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--secondary:hover' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'secondary_btn_border_color_hover',
            [
                'label'     => __( 'Border Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-btn--secondary:hover' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control(
            'secondary_btn_padding',
            [
                'label'      => __( 'Padding', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-btn--secondary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );

        $this->add_responsive_control(
            'secondary_btn_radius',
            [
                'label'      => __( 'Border Radius', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-btn--secondary' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE TAB - NAVIGATION (ARROWS & DOTS)
        // =========================================================================
        $this->start_controls_section(
            'section_style_navigation',
            [
                'label' => __( 'Navigation (Arrows & Dots)', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'nav_arrows_heading',
            [
                'label' => __( 'Arrows', 'xstore-child' ),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_responsive_control(
            'arrow_size',
            [
                'label'      => __( 'Arrow Size', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 28, 'max' => 80 ] ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_color',
            [
                'label'     => __( 'Arrow Icon Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-arrow' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_bg_color',
            [
                'label'     => __( 'Arrow Background', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-arrow' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_bg_hover',
            [
                'label'     => __( 'Arrow Hover Background', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-arrow:hover' => 'background-color: {{VALUE}}; color: #000000;',
                ],
            ]
        );

        $this->add_control(
            'nav_dots_heading',
            [
                'label'     => __( 'Dots', 'xstore-child' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'dot_size',
            [
                'label'      => __( 'Dot Size', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [ 'px' => [ 'min' => 6, 'max' => 24 ] ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'dot_color',
            [
                'label'     => __( 'Dot Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-dot' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dot_active_color',
            [
                'label'     => __( 'Active Dot Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-hero-dot.is-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'dots_offset_bottom',
            [
                'label'      => __( 'Dots Bottom Offset', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-hero-dots' => 'bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

    }

    /**
     * Render widget output on frontend
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $slides = ! empty( $settings['slides'] ) ? $settings['slides'] : [];
        if ( empty( $slides ) ) {
            return;
        }

        $total_slides = count( $slides );

        $slider_config = [
            'autoplay'           => ( 'yes' === $settings['autoplay'] ),
            'autoplaySpeed'      => ! empty( $settings['autoplay_speed'] ) ? absint( $settings['autoplay_speed'] ) : 5000,
            'pauseOnHover'       => ( 'yes' === $settings['pause_on_hover'] ),
            'pauseOnInteraction' => ( 'yes' === $settings['pause_on_interaction'] ),
            'infinite'           => ( 'yes' === $settings['infinite'] ),
            'transition'         => ! empty( $settings['transition_effect'] ) ? $settings['transition_effect'] : 'fade',
            'transitionSpeed'    => ! empty( $settings['transition_speed'] ) ? absint( $settings['transition_speed'] ) : 700,
            'swipe'              => ( 'yes' === $settings['swipe'] ),
            'keyboard'           => ( 'yes' === $settings['keyboard'] ),
            'total'              => $total_slides,
        ];

        $slider_classes = [ 'vf-hero-slider' ];
        $slider_classes[] = 'vf-effect-' . sanitize_html_class( $slider_config['transition'] );
        $slider_classes[] = 'vf-dots-' . sanitize_html_class( ! empty( $settings['dots_position'] ) ? $settings['dots_position'] : 'bottom-left' );
        $slider_classes[] = 'vf-height-' . sanitize_html_class( ! empty( $settings['slider_height_mode'] ) ? $settings['slider_height_mode'] : 'aspect' );
        if ( ! empty( $settings['content_animation'] ) ) {
            $slider_classes[] = 'vf-anim-' . sanitize_html_class( $settings['content_animation'] );
        }
        if ( ! empty( $settings['image_animation'] ) && 'none' === $settings['image_animation'] ) {
            $slider_classes[] = 'no-image-anim';
        }
        if ( 'yes' === $settings['image_zoom'] ) {
            $slider_classes[] = 'has-image-zoom';
        }
        if ( 'yes' === $settings['arrows_hide_mobile'] ) {
            $slider_classes[] = 'hide-arrows-mobile';
        }
        ?>

        <div class="<?php echo esc_attr( implode( ' ', $slider_classes ) ); ?>" 
             data-slider-config="<?php echo esc_attr( wp_json_encode( $slider_config ) ); ?>"
             role="region" 
             aria-roledescription="carousel" 
             aria-label="<?php echo esc_attr__( 'Hero Slider', 'xstore-child' ); ?>">

            <div class="vf-hero-track">
                <?php foreach ( $slides as $index => $slide ) :
                    $is_active = ( 0 === $index );
                    $slide_class = 'vf-hero-slide elementor-repeater-item-' . esc_attr( $slide['_id'] );
                    if ( $is_active ) {
                        $slide_class .= ' is-active is-loaded';
                    }

                    // Alignments
                    $content_styles   = [];
                    $container_styles = [];

                    if ( ! empty( $slide['content_align_override'] ) ) {
                        $content_styles[] = 'text-align:' . esc_attr( $slide['content_align_override'] );
                        $h_map = [
                            'left'   => 'flex-start',
                            'center' => 'center',
                            'right'  => 'flex-end',
                        ];
                        if ( isset( $h_map[ $slide['content_align_override'] ] ) ) {
                            $container_styles[] = 'justify-content:' . $h_map[ $slide['content_align_override'] ];
                        }
                    }

                    if ( ! empty( $slide['content_v_align_override'] ) ) {
                        $v_map = [
                            'top'    => 'flex-start',
                            'middle' => 'center',
                            'bottom' => 'flex-end',
                        ];
                        if ( isset( $v_map[ $slide['content_v_align_override'] ] ) ) {
                            $container_styles[] = 'align-items:' . $v_map[ $slide['content_v_align_override'] ];
                        }
                    }

                    // Images
                    $desktop_img_id  = ! empty( $slide['image_desktop']['id'] ) ? $slide['image_desktop']['id'] : 0;
                    $desktop_img_url = ! empty( $slide['image_desktop']['url'] ) ? $slide['image_desktop']['url'] : '';
                    $tablet_img_url  = ! empty( $slide['image_tablet']['url'] ) ? $slide['image_tablet']['url'] : $desktop_img_url;
                    $mobile_img_url  = ! empty( $slide['image_mobile']['url'] ) ? $slide['image_mobile']['url'] : $desktop_img_url;

                    // Alt & Dimensions
                    $alt_text   = ! empty( $slide['image_alt'] ) ? $slide['image_alt'] : '';
                    $img_width  = '';
                    $img_height = '';
                    if ( $desktop_img_id ) {
                        $img_src_data = wp_get_attachment_image_src( $desktop_img_id, 'full' );
                        if ( ! empty( $img_src_data ) ) {
                            $img_width  = ! empty( $img_src_data[1] ) ? $img_src_data[1] : '';
                            $img_height = ! empty( $img_src_data[2] ) ? $img_src_data[2] : '';
                        }
                        if ( empty( $alt_text ) ) {
                            $alt_text = get_post_meta( $desktop_img_id, '_wp_attachment_image_alt', true );
                        }
                    }
                    if ( empty( $alt_text ) ) {
                        $alt_text = wp_strip_all_tags( $slide['title'] );
                    }

                    // Priority on first slide
                    $loading_attr = ( 0 === $index && 'yes' === $settings['preload_first_image'] ) ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"';
                    ?>

                    <article class="<?php echo esc_attr( $slide_class ); ?>" 
                             data-slide-index="<?php echo esc_attr( $index ); ?>"
                             role="group" 
                             aria-roledescription="slide" 
                             aria-label="<?php echo esc_attr( sprintf( __( '%1$d of %2$d', 'xstore-child' ), $index + 1, $total_slides ) ); ?>"
                             <?php if ( ! $is_active ) echo 'aria-hidden="true"'; ?>>

                        <picture class="vf-hero-media">
                            <?php if ( ! empty( $mobile_img_url ) && $mobile_img_url !== $desktop_img_url ) : ?>
                                <source media="(max-width: 767px)" srcset="<?php echo esc_url( $mobile_img_url ); ?>">
                            <?php endif; ?>
                            <?php if ( ! empty( $tablet_img_url ) && $tablet_img_url !== $desktop_img_url ) : ?>
                                <source media="(max-width: 1024px)" srcset="<?php echo esc_url( $tablet_img_url ); ?>">
                            <?php endif; ?>
                            <img src="<?php echo esc_url( $desktop_img_url ); ?>" 
                                 alt="<?php echo esc_attr( $alt_text ); ?>" 
                                 <?php if ( ! empty( $img_width ) ) : ?>width="<?php echo esc_attr( $img_width ); ?>"<?php endif; ?>
                                 <?php if ( ! empty( $img_height ) ) : ?>height="<?php echo esc_attr( $img_height ); ?>"<?php endif; ?>
                                 class="vf-hero-img"
                                 <?php echo $loading_attr; ?> />
                        </picture>

                        <div class="vf-hero-overlay"></div>

                        <?php if ( ! empty( $slide['slide_link']['url'] ) ) :
                            $this->add_link_attributes( 'full_link_' . $index, $slide['slide_link'] );
                            ?>
                            <a class="vf-hero-full-link" <?php echo $this->get_render_attribute_string( 'full_link_' . $index ); ?> aria-label="<?php echo esc_attr( $alt_text ); ?>"></a>
                        <?php endif; ?>
                        <div class="vf-hero-content-container" <?php if ( ! empty( $container_styles ) ) echo 'style="' . esc_attr( implode( ';', $container_styles ) ) . '"'; ?>>
                            <div class="vf-hero-content" <?php if ( ! empty( $content_styles ) ) echo 'style="' . esc_attr( implode( ';', $content_styles ) ) . '"'; ?>>
                                <?php if ( ! empty( $slide['badge_text'] ) ) : ?>
                                    <span class="vf-hero-badge" style="--i:0;">
                                        <?php if ( ! empty( $slide['badge_icon']['value'] ) ) : ?>
                                            <span class="vf-hero-badge-icon"><?php Icons_Manager::render_icon( $slide['badge_icon'], [ 'aria-hidden' => 'true' ] ); ?></span>
                                        <?php endif; ?>
                                        <span class="vf-hero-badge-text"><?php echo esc_html( $slide['badge_text'] ); ?></span>
                                    </span>
                                <?php endif; ?>

                                <?php if ( ! empty( $slide['title'] ) ) :
                                    $tag = ! empty( $slide['title_tag'] ) ? $slide['title_tag'] : 'h2';
                                    $allowed_title_tags = [
                                        'br'     => [],
                                        'span'   => [ 'class' => [], 'style' => [] ],
                                        'strong' => [],
                                        'em'     => [],
                                    ];
                                    ?>
                                    <<?php echo esc_attr( $tag ); ?> class="vf-hero-title" style="--i:1;">
                                        <?php echo wp_kses( $slide['title'], $allowed_title_tags ); ?>
                                    </<?php echo esc_attr( $tag ); ?>>
                                <?php endif; ?>

                                <?php if ( ! empty( $slide['description'] ) ) : ?>
                                    <p class="vf-hero-desc" style="--i:2;">
                                        <?php echo wp_kses_post( $slide['description'] ); ?>
                                    </p>
                                <?php endif; ?>

                                <?php
                                $has_primary_btn   = ! empty( $slide['primary_btn_text'] ) && ! empty( $slide['primary_btn_link']['url'] );
                                $has_secondary_btn = ! empty( $slide['secondary_btn_text'] ) && ! empty( $slide['secondary_btn_link']['url'] );

                                if ( $has_primary_btn || $has_secondary_btn ) : ?>
                                    <div class="vf-hero-actions" style="--i:3;">
                                        <?php if ( $has_primary_btn ) :
                                            $this->add_link_attributes( 'primary_btn_' . $index, $slide['primary_btn_link'] );
                                            ?>
                                            <a class="vf-hero-btn vf-hero-btn--primary" <?php echo $this->get_render_attribute_string( 'primary_btn_' . $index ); ?>>
                                                <?php echo esc_html( $slide['primary_btn_text'] ); ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ( $has_secondary_btn ) :
                                            $this->add_link_attributes( 'secondary_btn_' . $index, $slide['secondary_btn_link'] );
                                            ?>
                                            <a class="vf-hero-btn vf-hero-btn--secondary" <?php echo $this->get_render_attribute_string( 'secondary_btn_' . $index ); ?>>
                                                <?php echo esc_html( $slide['secondary_btn_text'] ); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ( $total_slides > 1 && 'yes' === $settings['show_arrows'] ) : ?>
                <button type="button" class="vf-hero-arrow vf-hero-arrow--prev" aria-label="<?php echo esc_attr__( 'Previous slide', 'xstore-child' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button type="button" class="vf-hero-arrow vf-hero-arrow--next" aria-label="<?php echo esc_attr__( 'Next slide', 'xstore-child' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            <?php endif; ?>

            <?php if ( $total_slides > 1 && 'yes' === $settings['show_dots'] ) : ?>
                <div class="vf-hero-dots" role="tablist" aria-label="<?php echo esc_attr__( 'Slides selector', 'xstore-child' ); ?>">
                    <?php for ( $d = 0; $d < $total_slides; $d++ ) : ?>
                        <button type="button" 
                                class="vf-hero-dot<?php echo ( 0 === $d ) ? ' is-active' : ''; ?>" 
                                role="tab" 
                                aria-selected="<?php echo ( 0 === $d ) ? 'true' : 'false'; ?>" 
                                aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'xstore-child' ), $d + 1 ) ); ?>"
                                data-slide-target="<?php echo esc_attr( $d ); ?>">
                        </button>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

            <?php if ( $total_slides > 1 && 'yes' === $settings['show_progress'] ) : ?>
                <div class="vf-hero-progress" aria-hidden="true">
                    <span class="vf-hero-progress-bar"></span>
                </div>
            <?php endif; ?>

            <?php if ( $total_slides > 1 && 'yes' === $settings['show_counter'] ) : ?>
                <div class="vf-hero-counter" aria-hidden="true">
                    <span class="vf-counter-current">01</span> / <span class="vf-counter-total"><?php echo esc_html( sprintf( '%02d', $total_slides ) ); ?></span>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }
}
