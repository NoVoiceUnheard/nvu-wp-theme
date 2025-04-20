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
function novoiceunheard_strip_assets_on_links_page()
{
    if (is_page('links')) {
        // Keep these removed
        wp_dequeue_script('jquery');
        wp_dequeue_script('wp-embed');
        wp_dequeue_style('select2-css');
        wp_dequeue_script('select2-js');
        wp_dequeue_style('contact-form-7');
        wp_dequeue_script('contact-form-7');
        wp_dequeue_style('wp-sms');
        wp_dequeue_script('wp-sms');
        wp_dequeue_style('newsletter');
        wp_dequeue_script('newsletter');
        wp_dequeue_style('give');
        wp_dequeue_script('give');

        // Optional: Remove emoji scripts
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');

        // ✨ But *keep* the block styles for logo block!
        // So comment this one out or remove it:
        // wp_dequeue_style('wp-block-library');

        // If needed, also re-enable block editor JS (depends on how you load buttons):
        // wp_dequeue_script('wp-block-editor'); ← don’t run this line
    }
}
function novoiceunheard_enqueue_styles()
{
    $theme_version = wp_get_theme()->get('Version'); // Cache busting charm

    if (is_page('links') || is_page('map')) {
        // Only load brand.css on the /links page
        wp_enqueue_style(
            'brand-css',
            get_stylesheet_directory_uri() . '/brand.css',
            [],
            $theme_version
        );
        return;
    }

    // Only enqueue the parent style once!
    wp_enqueue_style('twentytwentyfive', get_template_directory_uri() . '/style.css'); // Parent theme stylesheet

    // Enqueue the child theme styles after the parent
    wp_enqueue_style('novoiceunheard', get_stylesheet_directory_uri() . '/style.css', ['twentytwentyfive'], $theme_version);

    // Select2 goodies
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], null, true);
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
function add_query_vars_to_body_class($classes)
{
    // Handle 'state'
    $state = get_query_var('state');
    if (!empty($state)) {
        if (is_array($state)) {
            foreach ($state as $val) {
                $classes[] = 'query-state-' . sanitize_html_class($val);
            }
        } else {
            $classes[] = 'query-state';
            $classes[] = 'query-state-' . sanitize_html_class($state);
        }
    }

    // Handle 'inquiry'
    $inquiry = get_query_var('inquiry');
    if (!empty($inquiry)) {
        $classes[] = 'query-inquiry';
        $classes[] = 'query-inquiry-' . sanitize_html_class($inquiry);
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

function add_google_analytics()
{
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-KFMGK95TZ9"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-KFMGK95TZ9');
    </script>
    <?php
}

function add_custom_admin_bar_link($wp_admin_bar)
{
    $pending_count = wp_count_posts('cf7_protest-listing')->pending ?? 0;
    $pending_org_count = wp_count_posts('cf7_organizations')->pending ?? 0;
    // Check if we are on the specific page
    if (!is_admin() && is_page('protest-listings')) {
        $wp_admin_bar->add_node(array(
            'id' => 'pending_protest_listings',
            'title' => "Pending Protest Listings ($pending_count)",
            'href' => admin_url('edit.php?post_status=pending&post_type=cf7_protest-listing'),
            'meta' => array('title' => 'View Pending Protest Listings')
        ));
    }
    if (!is_admin() && is_page('organizations')) {
        $wp_admin_bar->add_node(array(
            'id' => 'pending_organizations',
            'title' => "Pending Organizations ($pending_org_count)",
            'href' => admin_url('edit.php?post_status=pending&post_type=cf7_organizations'),
            'meta' => array('title' => 'View Pending Organizations')
        ));
    }
}

function add_pwa_manifest()
{
    // Define the path to your manifest file
    $manifest_path = get_stylesheet_directory_uri() . '/manifest.json'; // Adjust the path if it's located in a subfolder, like /assets/

    // Enqueue the manifest link tag in the head section
    echo '<link rel="manifest" href="' . esc_url($manifest_path) . '">';
}
function custom_dashboard_widget()
{
    wp_add_dashboard_widget(
        'custom_dashboard_card',
        'NoVoiceUnheard',
        'custom_dashboard_widget_display'
    );
    if (!current_user_can('administrator')) {
        // Remove "At a Glance" widget
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');

        // Remove "Activity" widget
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');

        // Remove "Quick Draft" widget
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');

        // Remove "WordPress News" widget
        remove_meta_box('dashboard_primary', 'dashboard', 'side');

        // Remove "Recent Comments" widget
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

        // Optionally remove other widgets added by plugins
        // remove_meta_box('plugin_widget_id', 'dashboard', 'normal'); // Example for plugin widgets
    }
}

function custom_dashboard_widget_display()
{
    // Retrieve saved content
    $content = get_option('custom_dashboard_card_content', '<p>Welcome to your dashboard! Edit this content.</p>');

    ?>
    <style>
        .dashboard-editor {
            display: none;
            margin-top: 10px;
        }

        .dashboard-card-preview {
            border: 1px solid #ccc;
            padding: 10px;
            background: #fff;
            margin-top: 10px;
        }
    </style>

    <div class="dashboard-card-preview">
        <div><?php echo wp_kses_post($content); ?></div>
    </div>

    <div class="dashboard-editor">
        <form method="post">
            <textarea name="dashboard_card_content"
                style="width:100%; height:100px;"><?php echo esc_textarea($content); ?></textarea>
            <br>
            <input type="submit" name="save_dashboard_card" value="Save" class="button button-primary">
        </form>
    </div>
    <p>
        <button id="toggle-editor" class="button">Toggle Editor</button>
    </p>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var toggleButton = document.getElementById("toggle-editor");
            var editorDiv = document.querySelector(".dashboard-editor");

            toggleButton.addEventListener("click", function () {
                if (editorDiv.style.display === "none" || editorDiv.style.display === "") {
                    editorDiv.style.display = "block";
                } else {
                    editorDiv.style.display = "none";
                }
            });
        });
    </script>

    <?php

    // Save the content when submitted
    if (isset($_POST['save_dashboard_card'])) {
        update_option('custom_dashboard_card_content', wp_kses_post($_POST['dashboard_card_content']));
        echo '<p style="color: green;">Saved! Refresh to see the changes.</p>';
    }
}

function novoiceunheard_contact_rewrite_rule()
{
    add_rewrite_rule('^contact/(general|press|volunteer)/?$', 'index.php?pagename=contact&inquiry=$matches[1]', 'top');

    add_rewrite_rule(
        '^protest-listings/feed/?$',
        'index.php?post_type=cf7_protest-listing&feed=rss2',
        'top'
    );
}
function add_custom_post_types_to_rss_feed($query)
{
    // Ensure we're working with the main query and the feed
    if ($query->is_feed() && $query->is_main_query()) {
        // Check if the feed is specifically for protest listings
        if (is_post_type_archive('cf7_protest-listing') || strpos($_SERVER['REQUEST_URI'], '/protest-listings/feed') !== false) {
            $query->set('post_type', 'cf7_protest-listing'); // Only 'cf7_protest-listing' for /protest-listings/feed/
        } else {
            $query->set('post_type', array('post', 'cf7_protest-listing')); // Regular feed includes both standard posts and protest listings
        }
    }
}

// Optional: Customize RSS content
function custom_rss_content($content)
{
    if (get_post_type() == 'cf7_protest-listing') {
        $custom_field = get_post_meta(get_the_ID(), 'custom_field_name', true);
        $content .= '<p><strong>Custom Field:</strong> ' . esc_html($custom_field) . '</p>';
    }
    return $content;
}
function novoiceunheard_register_query_vars($vars)
{
    $vars[] = 'inquiry';
    return $vars;
}
function custom_protest_listings_title($title)
{
    // Check if we're on the Protest Listings page with a 'state' query var
    if (is_page('protest-listings') && get_query_var('state')) {
        $state = get_query_var('state');  // Get the state from the URL
        $title = 'Protest Listings in ' . ucfirst(sanitize_text_field($state)) . ' - NoVoiceUnheard';
    }
    return $title;
}
// 1. Add a submenu page under "Tools"
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php', // Parent menu
        'Migrate State Tags', // Page title
        'Migrate State Tags', // Menu title
        'manage_options', // Capability
        'migrate-state-tags', // Slug
        'nvu_render_state_tag_migration_page' // Callback
    );
});

// 2. Render the admin page
function nvu_render_state_tag_migration_page() {
    if ( isset($_POST['nvu_migrate_tags']) && check_admin_referer('nvu_migrate_tags_action', 'nvu_migrate_tags_nonce') ) {
        nvu_migrate_state_tags();
    }

    ?>
    <div class="wrap">
        <h1>Migrate State Tags</h1>
        <form method="post">
            <?php wp_nonce_field('nvu_migrate_tags_action', 'nvu_migrate_tags_nonce'); ?>
            <p>This will copy old <code>post_tag</code> tags into your new <code>state</code> taxonomy on the updated posts.</p>
            <input type="submit" name="nvu_migrate_tags" class="button button-primary" value="Run Migration">
        </form>
    </div>
    <?php
}

// 3. Run the migration on submit
function nvu_migrate_state_tags() {
    $args = array(
        'post_type' => 'cf7_protest-listing',
        'posts_per_page' => -1,
    );

    $old_posts = get_posts( $args );
    $old_taxonomy = 'post_tag';
    $new_taxonomy = 'state';
    $count = 0;

    foreach ( $old_posts as $post ) {
        $terms = wp_get_object_terms($post->ID, 'post_tag', array('fields' => 'names'));
        if (!empty($terms) && !is_wp_error($terms)) {
            wp_set_object_terms($post->ID, $terms, 'state', true);
        }
        $count++;
    }

    echo '<div class="notice notice-success is-dismissible"><p>Migration complete. Tags copied to ' . $count . ' posts.</p></div>';
}
function nvunheard_add_state_rewrite_rules() {
    // Rule for the first page of the state archive
    add_rewrite_rule(
        '^protest-listings/state/([^/]+)/?$',
        'index.php?post_type=cf7_protest-listing&state=$matches[1]',
        'top'
    );

    // Rule for paginated pages of the state archive
    add_rewrite_rule(
        '^protest-listings/state/([^/]+)/page/([0-9]{1,})/?$',
        'index.php?post_type=cf7_protest-listing&state=$matches[1]&paged=$matches[2]',
        'top'
    );
    // Custom page: submit-listing
    add_rewrite_rule(
        '^protest-listings/submit-listing/?$',
        'index.php?pagename=protest-listings/submit-listing',
        'top'
    );
}