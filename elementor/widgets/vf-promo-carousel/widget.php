<?php
namespace VintageFootballElementorWidgets\Widgets\VFPromoCarousel;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_VFPromoCarousel extends Widget_Base {

    public function get_name() {
        return 'vf-promo-carousel';
    }

    public function get_title() {
        return __( 'VF Promo Carousel', 'xstore-child' );
    }

    public function get_icon() {
        return 'eicon-animation-text';
    }

    public function get_categories() {
        return [ 'vintage-football' ];
    }

    public function get_keywords() {
        return [ 'promo', 'marquee', 'carousel', 'banner', 'ticker', 'vintage', 'football' ];
    }

    public function get_style_depends() {
        return [ 'vf-promo-carousel-style' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_items',
            [
                'label' => __( 'Messages', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'text',
            [
                'label'       => __( 'Text', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'PREMIUM VINTAGE KITS', 'xstore-child' ),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'link',
            [
                'label'       => __( 'Link', 'xstore-child' ),
                'type'        => Controls_Manager::URL,
                'placeholder' => __( 'https://', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'items',
            [
                'label'       => __( 'Items', 'xstore-child' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    [ 'text' => __( 'NATIONAL TEAMS', 'xstore-child' ) ],
                    [ 'text' => __( 'PREMIUM VINTAGE KITS', 'xstore-child' ) ],
                    [ 'text' => __( 'FREE EXPRESS SHIPPING 7-10 DAYS', 'xstore-child' ) ],
                    [ 'text' => __( 'BUY TWO, GET ONE FREE !', 'xstore-child' ) ],
                ],
                'title_field' => '{{{ text }}}',
            ]
        );

        $this->add_control(
            'show_separator',
            [
                'label'        => __( 'Show Separator', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'separator_icon',
            [
                'label'     => __( 'Separator Icon', 'xstore-child' ),
                'type'      => Controls_Manager::ICONS,
                'condition' => [
                    'show_separator' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_motion',
            [
                'label' => __( 'Motion', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'direction',
            [
                'label'   => __( 'Direction', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'normal',
                'options' => [
                    'normal'  => __( 'To the left', 'xstore-child' ),
                    'reverse' => __( 'To the right', 'xstore-child' ),
                ],
                'selectors' => [
                    '{{WRAPPER}} .vf-promo__track' => 'animation-direction: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'speed',
            [
                'label'     => __( 'Loop duration (seconds)', 'xstore-child' ),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 6,
                'max'       => 120,
                'step'      => 1,
                'default'   => 28,
                'selectors' => [
                    '{{WRAPPER}} .vf-promo' => '--vf-promo-duration: {{VALUE}}s;',
                ],
            ]
        );

        $this->add_control(
            'pause_hover',
            [
                'label'        => __( 'Pause on hover', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_bar',
            [
                'label' => __( 'Bar', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'bar_color',
            [
                'label'     => __( 'Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-promo' => '--vf-promo-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'bar_padding',
            [
                'label'      => __( 'Vertical padding', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-promo' => '--vf-promo-pad: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_text',
            [
                'label' => __( 'Text', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'text_typography',
                'selector' => '{{WRAPPER}} .vf-promo__text',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label'     => __( 'Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-promo' => '--vf-promo-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_gap',
            [
                'label'      => __( 'Gap', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-promo' => '--vf-promo-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_icon',
            [
                'label'     => __( 'Separator', 'xstore-child' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_separator' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __( 'Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-promo__icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __( 'Size', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [
                        'min' => 6,
                        'max' => 40,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-promo' => '--vf-promo-icon: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $items    = isset( $settings['items'] ) ? $settings['items'] : [];
        $items    = array_values( array_filter( $items, function ( $item ) {
            return ! empty( $item['text'] );
        } ) );

        if ( empty( $items ) ) {
            return;
        }

        $classes = [ 'vf-promo' ];
        if ( ! empty( $settings['pause_hover'] ) && 'yes' === $settings['pause_hover'] ) {
            $classes[] = 'vf-promo--pause';
        }

        $symbol_id = 'vf-promo-star-' . $this->get_id();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <?php $this->render_star_symbol( $symbol_id ); ?>
            <div class="vf-promo__track">
                <?php
                $this->render_group( $items, $settings, $symbol_id, '' );
                $this->render_group( $items, $settings, $symbol_id, '-clone' );
                $this->render_group( $items, $settings, $symbol_id, '-clone' );
                ?>
            </div>
        </div>
        <?php
    }

    private function render_star_symbol( $symbol_id ) {
        ?>
        <svg class="vf-promo__sprite" aria-hidden="true" focusable="false">
            <symbol id="<?php echo esc_attr( $symbol_id ); ?>" viewBox="0 0 15 15">
                <path d="M7.47 14.756C7.338 13.976 6.99 13.136 6.426 12.236C5.862 11.324 5.058 10.478 4.014 9.698C2.982 8.918 1.95 8.42 0.918 8.204V7.448C1.938 7.208 2.916 6.764 3.852 6.116C4.8 5.456 5.592 4.664 6.228 3.74C6.876 2.792 7.29 1.856 7.47 0.932H8.226C8.334 1.532 8.55 2.15 8.874 2.786C9.198 3.41 9.612 4.01 10.116 4.586C10.632 5.15 11.208 5.66 11.844 6.116C12.792 6.788 13.758 7.232 14.742 7.448V8.204C14.082 8.336 13.398 8.606 12.69 9.014C11.994 9.422 11.346 9.908 10.746 10.472C10.146 11.024 9.654 11.606 9.27 12.218C8.706 13.118 8.358 13.964 8.226 14.756H7.47Z"></path>
            </symbol>
        </svg>
        <?php
    }

    private function render_group( $items, $settings, $symbol_id, $suffix ) {
        $show_separator = ! empty( $settings['show_separator'] ) && 'yes' === $settings['show_separator'];
        ?>
        <ul class="vf-promo__group"<?php echo $suffix ? ' aria-hidden="true"' : ''; ?>>
            <?php foreach ( $items as $item ) : ?>
                <li class="vf-promo__item">
                    <?php $this->render_text( $item, $suffix ); ?>
                    <?php if ( $show_separator ) : ?>
                        <span class="vf-promo__sep"><?php $this->render_separator( $settings, $symbol_id ); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    private function render_text( $item, $suffix ) {
        $text = isset( $item['text'] ) ? $item['text'] : '';
        $url  = isset( $item['link']['url'] ) ? $item['link']['url'] : '';

        if ( $url ) {
            $key = 'item-link-' . $item['_id'] . $suffix;
            $this->add_link_attributes( $key, $item['link'] );
            printf(
                '<a %1$s class="vf-promo__text">%2$s</a>',
                $this->get_render_attribute_string( $key ),
                esc_html( $text )
            );
            return;
        }

        printf( '<span class="vf-promo__text">%s</span>', esc_html( $text ) );
    }

    private function render_separator( $settings, $symbol_id ) {
        $icon = isset( $settings['separator_icon'] ) ? $settings['separator_icon'] : [];
        if ( ! empty( $icon['value'] ) ) {
            Icons_Manager::render_icon(
                $icon,
                [
                    'aria-hidden' => 'true',
                    'class'       => 'vf-promo__icon',
                ]
            );
            return;
        }
        ?>
        <svg class="vf-promo__icon" aria-hidden="true" focusable="false">
            <use href="#<?php echo esc_attr( $symbol_id ); ?>"></use>
        </svg>
        <?php
    }
}
