<?php
/*
    Plugin Name: Canny Armadillo
    Description: Add Canny Armadillo's The Armadillo to your website. Canny Armadillo provides Real Time Ad Tracking, Attribution & Analytics Software for Small Businesses. Will disable while user impersonated or while logged in as admin.
    Version: 1.0.0
    Author: Canny Armadillo
    Author URI: https://cannyarmadillo.com
*/

if (!defined('ABSPATH')) {
    die;
}

if (!defined('CANNY_ARMADILLO_PLUGIN')) {
    define('CANNY_ARMADILLO_PLUGIN', '1.0.0');
}

include_once 'CannyArmadillo.php';

$canny = new Canny_Armadillo();
$canny->init();

add_filter('script_loader_tag', function ($tag, $handle) {
    if ('canny-armadillo-armadillo' !== $handle) {
        return $tag;
    }

    return str_replace(' src', ' async src', $tag);
}, 10, 2);

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $url = esc_url(add_query_arg(
        'page',
        'canny-armadillo',
        get_admin_url() . 'admin.php'
    ));

    $settings_link = "<a href='$url'>" . __('Settings') . '</a>';

    $links[] = $settings_link;

    return $links;
});
