<?php
// Auto create pages
function novoiceunheard_create_default_pages()
{
    $pages = array(
        array(
            'title' => 'Home',
            'template' => 'home', // for templates/home.html
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
            $page_id = wp_insert_post(array(
                'post_title' => $page['title'],
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

// set default menu
function create_navigation_block_menu()
{
    if (get_posts(array('post_type' => 'wp_navigation'))) {
        return; // Exit if a navigation menu already exists
    }

    // Define navigation block with proper serialization
    $navigation_block = '<!-- wp:navigation-link {"label":"Home","url":"' . home_url('/') . '"} /-->
        <!-- wp:navigation-submenu {"label":"Take Action"} /-->

        <!-- wp:navigation-submenu {"label":"Get Informed"} /-->

        <!-- wp:navigation-submenu {"label":"Community Support"} /-->
        <!-- wp:navigation-submenu {"label":"NoVoiceUnheard","className":"wp-block-navigation__submenu-container"} -->
            <!-- wp:navigation-link {"label":"Contact","url":"' . home_url('/contact/') . '"} /-->
        <!-- /wp:navigation-submenu -->';

    // Insert as a navigation block post
    wp_insert_post(array(
        'post_title' => 'Navigation',
        'post_status' => 'publish',
        'post_type' => 'wp_navigation',
        'post_content' => $navigation_block,
    ));
}
add_action('after_switch_theme', 'create_navigation_block_menu');

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

/* Check & Notify if Plugins Are Missing */
function novoiceunheard_check_required_plugins()
{
    $required_plugins = [
        'contact-form-7/wp-contact-form-7.php',  // Contact Form 7
        'activitypub/activitypub.php', // ActivityPub
        'amp/amp.php', // AMP
        'cf7-registration/cf7-registration.php', // CF7 Registration
        'cf7-to-custom-post/cf7-to-custom-post.php', // CF7 to Custom Post
        'newsletter/plugin.php', // Newsletter
        'wp-sms/wp-sms.php', // WP SMS
    ];

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
                <a href="' . esc_url($install_url) . '">install</a> | 
                <a href="' . esc_url($activation_url) . '">activate</a>
            </li>';
        }
        echo '</ul></div>';
    }
}
add_action('admin_init', 'novoiceunheard_check_required_plugins');