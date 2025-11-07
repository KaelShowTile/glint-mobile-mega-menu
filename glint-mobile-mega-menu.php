<?php
/**
 * Plugin Name: CHT Mobile Menu
 * Description: Better mobile menu for GTO/CHT
 * Version: 1.1.0
 * Author: Kael
 */

defined('ABSPATH') || exit;

define('GLINT_MOBILE_MENU_PATH', plugin_dir_path(__FILE__));
define('GLINT_MOBILE_MENU_URL', plugin_dir_url(__FILE__));

// Create Database
register_activation_hook(__FILE__, 'glint_mobile_menu_activate');
function glint_mobile_menu_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'glint_mobile_mega_menu';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        parent_id mediumint(9) NOT NULL DEFAULT 0,
        menu_order int(11) NOT NULL DEFAULT 0,
        content longtext NOT NULL,
        level tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// load once
require_once GLINT_MOBILE_MENU_PATH . 'includes/class-admin.php';
require_once GLINT_MOBILE_MENU_PATH . 'includes/class-frontend.php';

// init plugin
add_action('plugins_loaded', 'glint_mobile_menu_init');
function glint_mobile_menu_init() {
    $admin = new Glint_Mobile_Menu_Admin();
    $frontend = new Glint_Mobile_Menu_Frontend();
}