<?php
// Enqueue parent theme styles
function novoiceunheard_enqueue_styles() {
    wp_enqueue_style('twentytwentyfive', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('novoiceunheard', get_stylesheet_directory_uri() . '/style.css', ['twentytwentyfive']);
}
add_action('wp_enqueue_scripts', 'novoiceunheard_enqueue_styles');
