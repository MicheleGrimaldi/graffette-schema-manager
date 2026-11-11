<?php
if (!defined('ABSPATH')) {
    exit;
}

function gsm_boot_output() {
    add_action('wp_head', 'gsm_output_schema_head', 30);
    add_action('wp_footer', 'gsm_output_schema_footer', 30);
    add_filter('wpseo_schema_graph', 'gsm_filter_yoast_graph', 20, 2);
    add_shortcode('graffette_schema', 'gsm_schema_shortcode');
}

function gsm_should_output_standalone($options) {
    // In modalità Yoast i nodi finiscono nel grafo di Yoast: niente secondo script.
    return !empty($options['enabled'])
        && ($options['output_mode'] ?? 'automatic') === 'automatic'
        && !gsm_is_yoast_mode($options);
}

function gsm_output_schema_head() {
    $options = gsm_get_options();

    if (!gsm_should_output_standalone($options)) {
        return;
    }

    if (($options['output_position'] ?? 'head') !== 'head') {
        return;
    }

    echo gsm_get_schema_script();
}

function gsm_output_schema_footer() {
    $options = gsm_get_options();

    if (!gsm_should_output_standalone($options)) {
        return;
    }

    if (($options['output_position'] ?? 'head') !== 'footer') {
        return;
    }

    echo gsm_get_schema_script();
}

function gsm_schema_shortcode($atts = []) {
    $atts = shortcode_atts([
        'debug' => 'false',
    ], $atts, 'graffette_schema');

    return gsm_get_schema_script($atts['debug'] === 'true');
}

function gsm_get_schema_script($force_debug = false) {
    $options = gsm_get_options();
    $schema = gsm_build_schema_graph();

    if (empty($schema)) {
        return '';
    }

    $json = wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if ($json === false || $json === '') {
        return '';
    }

    $output = "\n";

    if ($force_debug || (!empty($options['debug_comment']) && current_user_can('manage_options'))) {
        $output .= "<!-- Graffette Schema Manager: JSON-LD generated -->\n";
    }

    $output .= '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";

    return $output;
}

function graffette_schema() {
    echo gsm_get_schema_script();
}
