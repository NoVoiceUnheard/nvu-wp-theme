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

add_filter('body_class', 'add_query_params_to_body_class');

add_action('admin_init', 'novoiceunheard_check_required_plugins');

add_action('wp_head', 'add_google_analytics');