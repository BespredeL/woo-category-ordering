<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$meta_keys = $wpdb->get_col("SELECT DISTINCT meta_key FROM $wpdb->postmeta WHERE meta_key LIKE 'wco_cat_order_%'");
if ($meta_keys) {
    foreach ($meta_keys as $key) {
        $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $key));
    }
}

delete_option('woo_category_ordering_delete_data');
