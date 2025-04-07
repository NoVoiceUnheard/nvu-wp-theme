<?php
$includes = [
    'setup.php'
];

foreach ($includes as $file) {
    $filepath = get_stylesheet_directory() . "/includes/$file";
    if (file_exists($filepath)) {
        require_once $filepath;
    } else {
        error_log("Missing include file: $filepath");
    }
}

add_action('after_switch_theme', 'novoiceunheard_create_default_pages');

add_action('admin_init', 'create_navigation_block_menu');

add_action('wp_enqueue_scripts', 'novoiceunheard_enqueue_styles');
add_action('wp_enqueue_scripts', 'novoiceunheard_strip_assets_on_links_page', 100);

add_filter('body_class', 'add_query_vars_to_body_class');

add_action('admin_init', 'novoiceunheard_check_required_plugins');

add_action('wp_head', 'add_google_analytics');
// Hook into the admin bar
add_action('admin_bar_menu', 'add_custom_admin_bar_link', 100);

add_action( 'wp_head', 'add_pwa_manifest' );

add_action('wp_dashboard_setup', 'custom_dashboard_widget', 999);

add_action('init', 'novoiceunheard_contact_rewrite_rule');
add_filter('query_vars', 'novoiceunheard_register_query_vars');
add_action('pre_get_posts', 'add_custom_post_types_to_rss_feed');
add_filter('the_excerpt_rss', 'custom_rss_content');
add_filter('the_content_feed', 'custom_rss_content');