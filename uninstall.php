<?php
/**
 * Trigger this file on plugin unistall.
 * @package Sell eSIM
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

//clear Database stored data
delete_option('sellesim_settings');
delete_option('sellesim_dataplan_list');

// method 1
// $pluginData = get_posts(['post_type' => '', 'numberposts' => -1]);
// foreach ($pluginData as $item) {
//     wp_delete_post($item->ID, true);
// }

// method 2
// global $wpdb;
// $wpdb->query("DELETE FROM {database_prefix}_posts WHERE post_type= ''");
// $wpdb->query("DELETE FROM {database_prefix}_postmeta WHERE post_id NOT IN (SELECT ID FROM {database_prefix}_post)");
// $wpdb->query("DELETE FROM {database_prefix}_term_relationships WHERE object_id NOT IN (SELECT ID FROM {database_prefix}_post)");