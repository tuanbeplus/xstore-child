<?php
/**
 * Single blog post.
 *
 * Header and footer come from the theme. Markup follows
 * vintagefootball-blog-templates/single.html.
 *
 * @package xstore-child
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();

    $vf_post_id  = get_the_ID();
    $vf_category = vf_blog_primary_category( $vf_post_id );
    $vf_readtime = vf_blog_reading_time( $vf_post_id );
    $vf_permalink = get_permalink( $vf_post_id );
    $vf_share_url = rawurlencode( $vf_permalink );
    $vf_share_title = rawurlencode( get_the_title() );
    ?>

<main id="main" class="vf-main vf-article-wrap">
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

        <article id="post-<?php the_ID(); ?>" <?php post_class( 'vf-article' ); ?>>
            <header class="vf-article__header">
                <?php if ( $vf_category ) : ?>
                    <a class="vf-article__cat" href="<?php echo esc_url( get_category_link( $vf_category ) ); ?>">
                        <?php echo esc_html( $vf_category->name ); ?>
                    </a>
                <?php endif; ?>

                <h1 class="vf-article__title"><?php the_title(); ?></h1>

                <p class="vf-article__meta-row">
                    <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'j F, Y' ) ); ?></time>
                    <span aria-hidden="true">&middot;</span>
                    <?php echo esc_html( sprintf( _n( '%s min read', '%s min read', $vf_readtime, 'xstore-child' ), number_format_i18n( $vf_readtime ) ) ); ?>
                    <span aria-hidden="true">&middot;</span>
                    <span class="vf-article__author"><?php esc_html_e( 'Vintage Football Shop', 'xstore-child' ); ?></span>
                </p>
            </header>

            <figure class="vf-article__hero">
                <?php echo vf_blog_post_thumbnail( $vf_post_id, 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
                <?php if ( has_post_thumbnail() && wp_get_attachment_caption( get_post_thumbnail_id() ) ) : ?>
                    <figcaption><?php echo esc_html( wp_get_attachment_caption( get_post_thumbnail_id() ) ); ?></figcaption>
                <?php endif; ?>
            </figure>

            <div class="vf-article__content">
                <?php the_content(); ?>
                <?php
                wp_link_pages( array(
                    'before' => '<div class="vf-article__pages">',
                    'after'  => '</div>',
                ) );
                ?>
            </div>

            <div class="vf-share">
                <span class="vf-share__label"><?php esc_html_e( 'Share', 'xstore-child' ); ?></span>

                <a class="vf-share__btn" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . $vf_share_url ); ?>" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg>
                    Facebook
                </a>

                <a class="vf-share__btn" href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . $vf_share_url . '&text=' . $vf_share_title ); ?>" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.5 3h3.1l-6.8 7.8L22 21h-6.2l-4.8-6.3L5.7 21H2.6l7.3-8.3L2 3h6.4l4.4 5.8L17.5 3zm-1.1 16.2h1.7L7.7 4.7H5.9l10.5 14.5z"/></svg>
                    X
                </a>

                <a class="vf-share__btn" href="<?php echo esc_url( 'https://wa.me/?text=' . $vf_share_title . '%20' . $vf_share_url ); ?>" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2C6.5 2 2 6.4 2 11.86c0 1.74.5 3.44 1.4 4.93L2 22l5.37-1.4a10.1 10.1 0 0 0 4.67 1.13h.01c5.54 0 10.04-4.4 10.04-9.86C22.09 6.4 17.58 2 12.04 2zm0 18.02h-.01a8.4 8.4 0 0 1-4.28-1.17l-.31-.18-3.19.84.85-3.1-.2-.32a8.2 8.2 0 0 1-1.27-4.4c0-4.55 3.77-8.25 8.42-8.25 4.64 0 8.42 3.7 8.42 8.25 0 4.55-3.78 8.25-8.43 8.25zm4.62-6.18c-.25-.13-1.49-.73-1.72-.82-.23-.08-.4-.13-.57.13-.17.25-.65.82-.8 1-.15.17-.3.2-.55.07-.25-.13-1.06-.39-2.02-1.24-.75-.66-1.25-1.48-1.4-1.73-.15-.25-.02-.38.11-.51.12-.12.25-.3.38-.45.12-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.07-.13-.57-1.37-.78-1.88-.2-.48-.41-.42-.57-.42h-.49c-.17 0-.45.07-.68.32-.23.25-.9.88-.9 2.14s.92 2.48 1.05 2.65c.13.17 1.81 2.76 4.38 3.87 1.54.66 1.96.72 2.66.61.43-.07 1.49-.6 1.7-1.19.21-.58.21-1.08.15-1.19-.06-.1-.23-.17-.48-.3z"/></svg>
                    WhatsApp
                </a>

                <a class="vf-share__btn" href="<?php echo esc_url( 'mailto:?subject=' . $vf_share_title . '&body=' . $vf_share_url ); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    Email
                </a>
            </div>
        </article>

        <?php
        $vf_related = vf_blog_related_posts( $vf_post_id, 3 );

        if ( $vf_related ) :
            ?>
            <section class="vf-related" aria-labelledby="vf-related-heading">
                <h2 class="vf-related__title" id="vf-related-heading"><?php esc_html_e( 'Related posts', 'xstore-child' ); ?></h2>
                <div class="vf-related__grid">
                    <?php
                    foreach ( $vf_related as $vf_related_post ) :
                        $vf_related_cat = vf_blog_primary_category( $vf_related_post->ID );
                        ?>
                        <article class="vf-post-card">
                            <div class="vf-post-card__media">
                                <a class="vf-post-card__image" href="<?php echo esc_url( get_permalink( $vf_related_post->ID ) ); ?>">
                                    <?php echo vf_blog_post_thumbnail( $vf_related_post->ID, 'medium_large', array( 'loading' => 'lazy' ) ); ?>
                                </a>

                                <?php if ( $vf_related_cat ) : ?>
                                    <a class="vf-post-card__cat" href="<?php echo esc_url( get_category_link( $vf_related_cat ) ); ?>">
                                        <?php echo esc_html( $vf_related_cat->name ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <h3 class="vf-post-card__title">
                                <a href="<?php echo esc_url( get_permalink( $vf_related_post->ID ) ); ?>">
                                    <?php echo esc_html( get_the_title( $vf_related_post->ID ) ); ?>
                                </a>
                            </h3>

                            <p class="vf-post-card__meta">
                                <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $vf_related_post->ID ) ); ?>">
                                    <?php echo esc_html( get_the_date( 'j F, Y', $vf_related_post->ID ) ); ?>
                                </time>
                            </p>

                            <?php if ( has_excerpt( $vf_related_post->ID ) ) : ?>
                                <p class="vf-post-card__excerpt"><?php echo esc_html( get_the_excerpt( $vf_related_post->ID ) ); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php
        endif;

        $vf_product_ids = vf_blog_related_product_ids( $vf_post_id, 4 );

        if ( $vf_product_ids ) :
            ?>
            <section class="vf-products" aria-labelledby="vf-products-heading">
                <h2 class="vf-products__title" id="vf-products-heading"><?php esc_html_e( 'Related Kits', 'xstore-child' ); ?></h2>
                <?php vf_blog_render_product_loop( $vf_product_ids ); ?>
            </section>
            <?php
        endif;
        ?>
    </div>
</main>

    <?php
endwhile;

get_footer();
