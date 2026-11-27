<?php
/**
 * Blog index (posts page).
 *
 * Header and footer come from the theme. Markup follows
 * vintagefootball-blog-templates/archive.html.
 *
 * @package xstore-child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$vf_posts = array();

while ( have_posts() ) {
    the_post();
    $vf_posts[] = get_post();
}

$vf_page_for_posts = (int) get_option( 'page_for_posts' );
$vf_blog_title     = $vf_page_for_posts ? get_the_title( $vf_page_for_posts ) : __( 'Blog', 'xstore-child' );
$vf_blog_intro     = '';

if ( $vf_page_for_posts ) {
    $vf_blog_page_content = (string) get_post_field( 'post_content', $vf_page_for_posts );
    if ( '' !== trim( $vf_blog_page_content ) ) {
        $vf_blog_intro = wp_strip_all_tags( strip_shortcodes( $vf_blog_page_content ) );
    }
}

if ( '' === $vf_blog_intro ) {
    $vf_blog_intro = __( 'Stories from the collection: World Cup shirts, club classics, and practical notes on sizing and collecting retro football kits.', 'xstore-child' );
}

$vf_grid   = $vf_posts;
$vf_recent = vf_blog_recent_posts( 4 );

// Three overlay cards: flagged posts first, newest fills the rest.
$vf_top_stories = vf_blog_top_stories( 3 );
?>

<main id="main" class="vf-main vf-archive">
    <div class="vf-container">

        <?php
        vf_render_breadcrumbs(
            array(
                'show_return_button' => 'no',
                'show_home'          => 'yes',
                'show_current'       => 'yes',
                'text_transform'     => 'none',
                'context'            => '',
            )
        );
        ?>

        <header class="vf-page-head">
            <h1 class="vf-page-head__title"><?php echo esc_html( $vf_blog_title ); ?></h1>
            <p class="vf-page-head__intro"><?php echo esc_html( $vf_blog_intro ); ?></p>
        </header>

        <div class="vf-archive__layout">
            <div class="vf-archive__main">

                <?php if ( $vf_top_stories ) : ?>
                    <?php $vf_top_stories_side = array_slice( $vf_top_stories, 1 ); ?>
                    <section class="vf-top-stories" aria-label="<?php esc_attr_e( 'Top stories', 'xstore-child' ); ?>">
                        <div class="vf-top-stories__grid<?php echo $vf_top_stories_side ? '' : ' vf-top-stories__grid--single'; ?>">
                            <?php echo vf_blog_render_overlay_card( $vf_top_stories[0], true ); ?>

                            <?php if ( $vf_top_stories_side ) : ?>
                                <div class="vf-top-stories__side">
                                    <?php foreach ( $vf_top_stories_side as $vf_top_story ) : ?>
                                        <?php echo vf_blog_render_overlay_card( $vf_top_story ); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <div class="vf-archive__content" aria-live="polite">

                <?php vf_blog_render_category_chips(); ?>

<?php if ( $vf_grid ) : ?>
                    <div class="vf-post-grid">
                        <?php
                        foreach ( $vf_grid as $vf_post ) :
                            setup_postdata( $GLOBALS['post'] = $vf_post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            $vf_cat = vf_blog_primary_category( $vf_post->ID );
                            ?>
                            <article class="vf-post-card">
                                <div class="vf-post-card__media">
                                    <a class="vf-post-card__image" href="<?php echo esc_url( get_permalink( $vf_post->ID ) ); ?>">
                                        <?php echo vf_blog_post_thumbnail( $vf_post->ID, 'medium_large', array( 'loading' => 'lazy', 'sizes' => '(max-width: 767px) calc(100vw - 32px), (max-width: 1024px) calc(50vw - 40px), 420px' ) ); ?>
                                    </a>

                                    <?php if ( $vf_cat ) : ?>
                                        <a class="vf-post-card__cat" href="<?php echo esc_url( get_category_link( $vf_cat ) ); ?>">
                                            <?php echo esc_html( $vf_cat->name ); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <h3 class="vf-post-card__title">
                                    <a href="<?php echo esc_url( get_permalink( $vf_post->ID ) ); ?>">
                                        <?php echo esc_html( get_the_title( $vf_post->ID ) ); ?>
                                    </a>
                                </h3>

                                <p class="vf-post-card__meta">
                                    <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $vf_post->ID ) ); ?>">
                                        <?php echo esc_html( get_the_date( 'j F, Y', $vf_post->ID ) ); ?>
                                    </time>
                                </p>

                                <?php if ( has_excerpt( $vf_post->ID ) ) : ?>
                                    <p class="vf-post-card__excerpt"><?php echo esc_html( get_the_excerpt( $vf_post->ID ) ); ?></p>
                                <?php endif; ?>
                            </article>
                            <?php
                        endforeach;
                        wp_reset_postdata();
                        ?>
                    </div>
                <?php else : ?>
                    <p class="vf-archive__empty"><?php esc_html_e( 'No posts found for this category yet.', 'xstore-child' ); ?></p>
                <?php endif; ?>

                <?php vf_blog_render_pagination(); ?>
                </div>
            </div>

            <aside class="vf-sidebar" aria-label="<?php esc_attr_e( 'Blog sidebar', 'xstore-child' ); ?>">
                <?php if ( $vf_recent ) : ?>
                    <div class="vf-widget">
                        <h2 class="vf-widget__title"><?php esc_html_e( 'Recent posts', 'xstore-child' ); ?></h2>
                        <ul class="vf-widget-recent">
                            <?php foreach ( $vf_recent as $vf_recent_post ) : ?>
                                <li>
                                    <a class="vf-widget-recent__item" href="<?php echo esc_url( get_permalink( $vf_recent_post->ID ) ); ?>">
                                        <?php echo vf_blog_post_thumbnail( $vf_recent_post->ID, 'thumbnail', array( 'loading' => 'lazy', 'width' => 72, 'height' => 72, 'sizes' => '72px' ) ); ?>
                                        <div>
                                            <h3><?php echo esc_html( get_the_title( $vf_recent_post->ID ) ); ?></h3>
                                            <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $vf_recent_post->ID ) ); ?>">
                                                <?php echo esc_html( get_the_date( 'j F, Y', $vf_recent_post->ID ) ); ?>
                                            </time>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <aside class="vf-widget vf-widget-promo">
                    <span class="vf-widget-promo__kicker"><?php esc_html_e( 'Buy 2 Get 1 Free', 'xstore-child' ); ?></span>
                    <h3><?php esc_html_e( 'The offer on the live shop', 'xstore-child' ); ?></h3>
                    <p><?php esc_html_e( 'Free shipping on all orders worldwide, plus the current buy-two-get-one-free offer. See terms on the shop.', 'xstore-child' ); ?></p>
                    <a class="vf-btn vf-btn--on-dark" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Shop Now', 'xstore-child' ); ?></a>
                </aside>
            </aside>
        </div>
    </div>
</main>

<?php
get_footer();
