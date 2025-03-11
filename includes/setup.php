<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
// set default menu
function create_navigation_block_menu()
{
    if (get_posts(array('post_type' => 'wp_navigation'))) {
        return; // Exit if a navigation menu already exists
    }
    $template_path = require get_stylesheet_directory() . '/includes/navigation_block.php';
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        error_log("Navigation template missing: $template_path");
        return;
    }

    // Insert as a navigation block post
    wp_insert_post(array(
        'post_title' => 'Navigation',
        'post_status' => 'publish',
        'post_type' => 'wp_navigation',
        'post_content' => $navigation_block,
    ));
}
// Enqueue parent theme styles
function novoiceunheard_enqueue_styles()
{
    wp_enqueue_style('twentytwentyfive', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('novoiceunheard', get_stylesheet_directory_uri() . '/style.css', ['twentytwentyfive']);
}

/* Check & Notify if Plugins Are Missing */
function novoiceunheard_check_required_plugins()
{
    $required_plugins = require get_stylesheet_directory() . '/includes/required_plugins.php';
    $missing_plugins = [];

    foreach ($required_plugins as $plugin) {
        if (!is_plugin_active($plugin)) {
            $missing_plugins[] = $plugin;
        }
    }

    if (!empty($missing_plugins)) {
        echo '<div class="notice notice-error"><p><strong>Required Plugins Missing:</strong> Please install and activate the following plugins:</p><ul>';
        foreach ($missing_plugins as $plugin) {
            $plugin_slug = dirname($plugin); // Plugin folder
            $plugin_file = $plugin; // Full path including .php file

            $nonce = wp_create_nonce('activate-plugin_' . $plugin_file);
            $activation_url = wp_nonce_url(admin_url('plugins.php?action=activate&plugin=' . $plugin_file), 'activate-plugin_' . $plugin_file);

            $install_url = admin_url('plugin-install.php?s=' . $plugin_slug . '&tab=search&type=term');

            echo '<li>' . esc_html($plugin_slug) . ': 
                <a href="' . esc_url($install_url) . '">Install</a> | 
                <a href="' . esc_url($activation_url) . '">Activate</a>
            </li>';
        }
        echo '</ul></div>';
    }
}
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
// Auto create pages
function novoiceunheard_create_default_pages()
{
    $pages = array(
        array(
            'title' => 'Home',
            'template' => 'front-page', // for templates/home.html
        ),
        array(
            'title' => 'Organizations',
            'template' => 'organizations',
        ),
        array(
            'title' => 'Submit Organization',
            'template' => 'organizer-submit',
            'parent' => 'organizations',
        ),
        array(
            'title' => 'Contact',
            'template' => 'contact',
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
            $page_parent = null;
            if ($page['parent']) {
                $parent = get_page_by_path($page['parent']);

                if ($parent) {
                    $page_parent = $parent->ID;
                } else {
                    echo "Page not found.";
                }
            }
            $page_id = wp_insert_post(array(
                'post_title' => $page['title'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_parent' => $page_parent
            ));


            if (!empty($page['template'])) {
                update_post_meta($page_id, '_wp_page_template', $page['template']);
            }
        }
        wp_reset_postdata();
    }
}