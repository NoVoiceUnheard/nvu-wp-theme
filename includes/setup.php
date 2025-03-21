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
    $template_path = dirname(__FILE__) . '/navigation_block.php';
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        error_log("Navigation template missing: $template_path");
        return;
    }

    // Insert as a navigation block post
    wp_insert_post(array(
        'post_title' => 'NVU Navigation',
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
    // Enqueue Select2 CSS
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');

    // Enqueue Select2 JS
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), null, true);
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
            
            if (is_array($value)) {
                foreach ($value as $sub_value) {
                    $sanitized_sub_value = sanitize_html_class($sub_value);
                    $classes[] = "query-{$sanitized_key}-{$sanitized_sub_value}";
                }
            } else {
                $sanitized_value = sanitize_html_class($value);
                $classes[] = "query-{$sanitized_key}";
                if (!empty($sanitized_value)) {
                    $classes[] = "query-{$sanitized_key}-{$sanitized_value}";
                }
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
            'template' => 'front-page', // for templates/front-page.html
        ),
        array(
            'title' => 'Protest Listings',
            'template' => 'protest-listings',
        ),
        array(
            'title' => 'Submit Listing',
            'template' => 'listing-submit',
            'parent' => 'protest-listings',
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

function add_google_analytics() {
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-KFMGK95TZ9"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-KFMGK95TZ9');
    </script>
    <?php
}

function add_custom_admin_bar_link($wp_admin_bar) {
    $pending_count = wp_count_posts('cf7_protest-listing')->pending ?? 0;
    $pending_org_count = wp_count_posts('cf7_organizations')->pending ?? 0;
    // Check if we are on the specific page
    if (!is_admin() && is_page('protest-listings')) {  
        $wp_admin_bar->add_node(array(
            'id'    => 'pending_protest_listings',
            'title' => "Pending Protest Listings ($pending_count)",
            'href'  => admin_url('edit.php?post_status=pending&post_type=cf7_protest-listing'),
            'meta'  => array('title' => 'View Pending Protest Listings')
        ));
    }
    if (!is_admin() && is_page('organizations')) {  
        $wp_admin_bar->add_node(array(
            'id'    => 'pending_organizations',
            'title' => "Pending Organizations ($pending_org_count)",
            'href'  => admin_url('edit.php?post_status=pending&post_type=cf7_organizations'),
            'meta'  => array('title' => 'View Pending Organizations')
        ));
    }
}

function add_pwa_manifest() {
    // Define the path to your manifest file
    $manifest_path = get_stylesheet_directory_uri() . '/manifest.json'; // Adjust the path if it's located in a subfolder, like /assets/
    
    // Enqueue the manifest link tag in the head section
    echo '<link rel="manifest" href="' . esc_url( $manifest_path ) . '">';
}