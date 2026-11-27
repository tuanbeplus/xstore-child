<?php
/**
 * Admin posts list: "Image" and "Featured" columns right after Title.
 *
 * Featured reads the ACF true/false field "vf_featured_post", the same meta
 * used by vf_blog_featured_posts() on the blog index.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Insert the columns directly after the Title column.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function vf_admin_post_columns( $columns ) {
    $new = array();

    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;

        if ( 'title' === $key ) {
            $new['vf_image']    = __( 'Image', 'xstore-child' );
            $new['vf_featured'] = __( 'Featured', 'xstore-child' );
        }
    }

    return $new;
}
add_filter( 'manage_post_posts_columns', 'vf_admin_post_columns' );

/**
 * Render the custom column content.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function vf_admin_post_column_content( $column, $post_id ) {
    if ( 'vf_image' === $column ) {
        $thumb_id = (int) get_post_thumbnail_id( $post_id );
        $src      = $thumb_id ? wp_get_attachment_image_src( $thumb_id, 'thumbnail' ) : false;

        if ( $src ) {
            printf(
                '<img src="%1$s" alt="" width="56" height="56" class="vf-admin-thumb" />',
                esc_url( $src[0] )
            );
            return;
        }

        printf(
            '<img src="%1$s" alt="" width="56" height="56" class="vf-admin-thumb vf-admin-thumb--placeholder" />',
            esc_url( get_stylesheet_directory_uri() . '/assets/img/blog-placeholder.svg' )
        );
        return;
    }

    if ( 'vf_featured' === $column ) {
        $is_featured = (bool) get_post_meta( $post_id, 'vf_featured_post', true );

        if ( $is_featured ) {
            printf(
                '<span class="vf-admin-flag vf-admin-flag--on" title="%1$s">&#10003; %2$s</span>',
                esc_attr__( 'Featured on the blog index', 'xstore-child' ),
                esc_html__( 'Featured', 'xstore-child' )
            );
            return;
        }

        printf(
            '<span class="vf-admin-flag vf-admin-flag--off" title="%1$s" aria-label="%1$s">&mdash;</span>',
            esc_attr__( 'Not featured', 'xstore-child' )
        );
    }
}
add_action( 'manage_post_posts_custom_column', 'vf_admin_post_column_content', 10, 2 );

/**
 * Column widths and thumbnail styling on the posts list screen.
 */
function vf_admin_post_columns_css() {
    $screen = get_current_screen();

    if ( ! $screen || 'edit-post' !== $screen->id ) {
        return;
    }

    echo '<style>
        .fixed .column-vf_image{width:76px}
        .fixed .column-vf_featured{width:104px}
        .vf-admin-thumb{width:56px;height:56px;object-fit:cover;border-radius:6px;display:block;background:#f2f2f2}
        .vf-admin-thumb--placeholder{object-fit:contain;padding:6px;border:1px solid #e5e5e5;border-radius:6px;box-sizing:border-box}
        .vf-admin-flag{display:inline-block;font-size:12px;line-height:1.6;border-radius:3px;padding:0 6px;white-space:nowrap}
        .vf-admin-flag--on{background:#edfaef;color:#007017;font-weight:600}
        .vf-admin-flag--off{color:#8c8f94}
    </style>';
}
add_action( 'admin_head-edit.php', 'vf_admin_post_columns_css' );
