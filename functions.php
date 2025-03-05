<?php
// Enqueue parent theme styles
function novoiceunheard_enqueue_styles()
{
    wp_enqueue_style('twentytwentyfive', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('novoiceunheard', get_stylesheet_directory_uri() . '/style.css', ['twentytwentyfive']);
}
add_action('wp_enqueue_scripts', 'novoiceunheard_enqueue_styles');

// add query params to body class
function add_query_params_to_body_class($classes)
{
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

// Auto create pages
function novoiceunheard_create_default_pages()
{
    $pages = array(
        array(
            'title' => 'Home',
            'template' => 'home',
            'content' => ''
        ),
        array(
            'title' => 'Contact',
            'template' => 'contact',
            'content' => ''
        ),
    );

    foreach ($pages as $page) {
        $query = new WP_Query([
            'post_type' => 'page',
            'title' => $page['title'],
            'post_status' => 'publish',
            'posts_per_page' => 1
        ]);

        if (!$query->have_posts()) {
            $page_id = wp_insert_post(array(
                'post_title' => $page['title'],
                'post_content' => $page['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
            ));

            if (!empty($page['template'])) {
                update_post_meta($page_id, '_wp_page_template', $page['template']);
            }
        }
        wp_reset_postdata();
    }
}

add_action('after_switch_theme', 'novoiceunheard_create_default_pages');