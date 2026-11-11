<?php
/**
 * Plugin Name: Graffette Schema Manager
 * Description: Genera JSON-LD per LocalBusiness e Person con impostazioni globali, orari di apertura e output automatico/shortcode.
 * Version: 0.2.1
 * Author: Graffette
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: graffette-schema-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GSM_VERSION', '0.2.1');
define('GSM_PLUGIN_FILE', __FILE__);
define('GSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GSM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once GSM_PLUGIN_DIR . 'includes/helpers.php';
require_once GSM_PLUGIN_DIR . 'includes/sanitize.php';
require_once GSM_PLUGIN_DIR . 'includes/schema-builder.php';
require_once GSM_PLUGIN_DIR . 'includes/output.php';
require_once GSM_PLUGIN_DIR . 'includes/admin-page.php';

register_activation_hook(__FILE__, 'gsm_activate_plugin');

function gsm_activate_plugin() {
    $existing = get_option('graffette_schema_options', null);

    if ($existing === null) {
        add_option('graffette_schema_options', gsm_get_default_options());
    }
}

add_action('plugins_loaded', 'gsm_boot_plugin');

function gsm_boot_plugin() {
    if (is_admin()) {
        gsm_boot_admin_page();
    }

    gsm_boot_output();
}

add_action('update_option_graffette_schema_options', 'gsm_register_translatable_strings');
add_action('add_option_graffette_schema_options', 'gsm_register_translatable_strings');

// Rende la descrizione traducibile in WPML > Traduzione stringhe.
function gsm_register_translatable_strings() {
    $options = gsm_get_options();
    $description = trim((string) ($options['business_description'] ?? ''));

    if ($description !== '') {
        do_action('wpml_register_single_string', 'graffette-schema-manager', 'business_description', $description);
    }
}
