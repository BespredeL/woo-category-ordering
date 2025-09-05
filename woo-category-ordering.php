<?php
/**
 * Plugin Name: WooCommerce Category Product Ordering
 * Description: Manual ordering of WooCommerce products within categories using drag & drop.
 * Author: Aleksandr BespredeL Kireev
 * Author URI: https://bespredel.name
 * Version: 1.3.1
 * Text Domain: woo-category-ordering
 * Domain Path: /languages
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Connecting the basic logic of the plugin
 */
require_once plugin_dir_path(__FILE__) . 'class-woo-category-ordering.php';

add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        'woo-category-ordering',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
});

new WooCategoryOrdering();