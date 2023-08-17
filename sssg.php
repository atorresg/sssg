<?php
/*
Plugin Name: Simple Static Site Generator
Plugin URI: https://github.com/atorresg/sssg/
Description: This plugin generates a static copy of your WordPress site based on the configurations set by the user.
Version: 1.0.0
Author: Alejandro Torres
Author URI: https://github.com/atorresg/
License: GPL2
Text Domain: sssg
*/

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

define('SSSG_PATH', plugin_dir_path(__FILE__));
define('SSSG_URL', plugin_dir_url(__FILE__));

// Include our plugin classes
require_once SSSG_PATH . 'inc/class-sssg-options.php';
require_once SSSG_PATH . 'inc/class-sssg-export.php';

function sssg_init()
{
    // Initialize our classes
    $sssg_options = new SSSG_Options();
    $sssg_export = new SSSG_Export();
}
add_action('plugins_loaded', 'sssg_init');
if (isset($_GET['sssg_export'])) {
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    add_action('init', 'smartwp_disable_emojis');
}


function smartwp_disable_emojis()
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
    //Remove the REST API endpoint.
    remove_action('rest_api_init', 'wp_oembed_register_route');

    //Remove oEmbed JavaScript from the front-end and back-end.
    remove_action('wp_head', 'wp_oembed_add_host_js');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
    remove_action('wp_head', 'wlwmanifest_link');
    // Remove REST API link tag
    remove_action('wp_head', 'rest_output_link_wp_head', 10);

    // Turn off oEmbed auto discovery.
    add_filter('embed_oembed_discover', '__return_false');

    //Don't filter oEmbed results.
    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    // Remove oEmbed Discovery Links
    remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);

    // Remove XMLRPC link tag
    remove_action('wp_head', 'rsd_link', 10);
}

function disable_emojis_tinymce($plugins)
{
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    } else {
        return array();
    }
}
