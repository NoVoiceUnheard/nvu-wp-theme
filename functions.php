<?php
// Enqueue parent theme styles
function novoiceunheard_enqueue_styles() {
    wp_enqueue_style('twentytwentyfive', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('novoiceunheard', get_stylesheet_directory_uri() . '/style.css', ['twentytwentyfive']);
}
add_action('wp_enqueue_scripts', 'novoiceunheard_enqueue_styles');

// add query params to body class
function add_query_params_to_body_class($classes) {
    if (!empty($_GET)) {
        foreach ($_GET as $key => $value) {
            $sanitized_key = sanitize_html_class($key);
            $sanitized_value = sanitize_html_class($value);
            $classes[] = "query-{$sanitized_key}";
            if (!empty($sanitized_value)) {
                $classes[] = "query-{$sanitized_key}-{$sanitized_value}";
            }
        }
    }
    return $classes;
}
add_filter('body_class', 'add_query_params_to_body_class');