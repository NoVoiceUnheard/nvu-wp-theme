<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define the navigation block content
$navigation_block = '<!-- wp:navigation-link {"label":"Home","url":"' . home_url('/') . '"} /-->
<!-- wp:navigation-submenu {"label":"Take Action"} -->
<!-- wp:navigation-link {"label":"Protest Listings","type":"page","id":115,"url":"' . home_url('/protest-listings/') . '","kind":"post-type"} /-->
<!-- /wp:navigation-submenu -->
<!-- wp:navigation-submenu {"label":"Get Informed"} /-->

<!-- wp:navigation-submenu {"label":"Community Support"} -->
    <!-- wp:navigation-link {"label":"Organization Catalog","url":"' . home_url('/organizations/') . '"} /-->
<!-- /wp:navigation-submenu -->

<!-- wp:navigation-submenu {"label":"NoVoiceUnheard","className":"wp-block-navigation__submenu-container"} -->
    <!-- wp:navigation-link {"label":"Contact","url":"' . home_url('/contact/') . '"} /-->
<!-- /wp:navigation-submenu -->';