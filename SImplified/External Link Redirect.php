<?php
/*
Plugin Name: External Link Redirect
Description: Redirect specific external links to custom destinations.
Version: 1.1
Author: Michael Tallada
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function enqueue_external_redirect_script() {
    wp_enqueue_script(
        'external-redirect',
        plugin_dir_url(__FILE__) . 'external-redirect.js',
        [],
        '1.1',
        true
    );

    // Localize the redirect mapping for use in JavaScript.
    $redirects = [
        'https://shorturl.at/KaDb8' => 'https://82bet.com/#/register?invitationCode=668323190180',
        // Add more redirects here.
    ];
    wp_localize_script('external-redirect', 'redirectMapping', $redirects);
}
add_action('wp_enqueue_scripts', 'enqueue_external_redirect_script');
