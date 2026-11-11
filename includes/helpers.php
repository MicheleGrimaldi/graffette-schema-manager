<?php
if (!defined('ABSPATH')) {
    exit;
}

function gsm_get_default_options() {
    return [
        'enabled' => 0,
        'output_mode' => 'automatic',
        'output_position' => 'head',
        'debug_comment' => 0,
        'yoast_integration' => 1,

        'local_business_enabled' => 1,
        'local_business_scope' => 'front_page',
        'business_type' => 'LocalBusiness',
        'business_type_custom' => '',
        'business_name' => get_bloginfo('name'),
        'business_legal_name' => '',
        'business_url' => '',
        'business_description' => get_bloginfo('description'),
        'business_logo_url' => '',
        'business_image_url' => '',
        'business_telephone' => '',
        'business_email' => '',
        'business_price_range' => '',
        'business_area_served' => '',
        'business_tax_id' => '',
        'business_vat_id' => '',
        'business_languages' => '',
        'business_currencies' => '',
        'contact_point_type' => 'customer service',

        'address_street' => '',
        'address_postal_code' => '',
        'address_city' => '',
        'address_region' => '',
        'address_country' => 'IT',
        'geo_latitude' => '',
        'geo_longitude' => '',
        'google_maps_url' => '',

        'same_as' => '',
        'opening_hours' => gsm_get_default_opening_hours(),

        'person_enabled' => 0,
        'person_name' => '',
        'person_job_title' => '',
        'person_description' => '',
        'person_url' => '',
        'person_image_url' => '',
        'person_email' => '',
        'person_telephone' => '',
        'person_same_as' => '',
        'person_works_for_business' => 1,
    ];
}

function gsm_get_default_opening_hours() {
    $days = gsm_get_days();
    $hours = [];

    foreach ($days as $day_key => $day_label) {
        $is_weekend = in_array($day_key, ['saturday', 'sunday'], true);

        $hours[$day_key] = [
            'closed' => $is_weekend ? 1 : 0,
            'slots' => [
                'morning' => [
                    'opens' => $is_weekend ? '' : '09:00',
                    'closes' => $is_weekend ? '' : '13:00',
                ],
                'afternoon' => [
                    'opens' => $is_weekend ? '' : '14:00',
                    'closes' => $is_weekend ? '' : '18:00',
                ],
            ],
        ];
    }

    return $hours;
}

function gsm_get_days() {
    return [
        'monday' => __('Lunedì', 'graffette-schema-manager'),
        'tuesday' => __('Martedì', 'graffette-schema-manager'),
        'wednesday' => __('Mercoledì', 'graffette-schema-manager'),
        'thursday' => __('Giovedì', 'graffette-schema-manager'),
        'friday' => __('Venerdì', 'graffette-schema-manager'),
        'saturday' => __('Sabato', 'graffette-schema-manager'),
        'sunday' => __('Domenica', 'graffette-schema-manager'),
    ];
}

function gsm_get_schema_days() {
    return [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];
}

function gsm_get_business_types() {
    return [
        'LocalBusiness' => 'LocalBusiness',
        'ProfessionalService' => 'ProfessionalService',
        'LegalService' => 'LegalService',
        'AccountingService' => 'AccountingService',
        'Dentist' => 'Dentist',
        'MedicalBusiness' => 'MedicalBusiness',
        'MedicalClinic' => 'MedicalClinic',
        'Store' => 'Store',
        'Restaurant' => 'Restaurant',
        'RealEstateAgent' => 'RealEstateAgent',
        'HomeAndConstructionBusiness' => 'HomeAndConstructionBusiness',
        'AutomotiveBusiness' => 'AutomotiveBusiness',
        'HealthAndBeautyBusiness' => 'HealthAndBeautyBusiness',
        'Custom' => __('Custom', 'graffette-schema-manager'),
    ];
}

function gsm_get_options() {
    $defaults = gsm_get_default_options();
    $options = get_option('graffette_schema_options', []);

    if (!is_array($options)) {
        $options = [];
    }

    return array_replace_recursive($defaults, $options);
}

function gsm_is_valid_time($time) {
    return is_string($time) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time);
}

function gsm_url_or_empty($url) {
    $url = trim((string) $url);

    if ($url === '') {
        return '';
    }

    return esc_url_raw($url);
}

function gsm_text_or_empty($value) {
    return sanitize_text_field((string) $value);
}

function gsm_textarea_lines_to_array($value) {
    $value = (string) $value;
    $lines = preg_split('/\r\n|\r|\n/', $value);
    $items = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $url = esc_url_raw($line);

        if ($url !== '') {
            $items[] = $url;
        }
    }

    return array_values(array_unique($items));
}

function gsm_textarea_lines_to_text_array($value) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $value);
    $items = [];

    foreach ($lines as $line) {
        $line = sanitize_text_field($line);

        if ($line !== '') {
            $items[] = $line;
        }
    }

    return array_values(array_unique($items));
}

function gsm_get_business_url($options) {
    $url = gsm_url_or_empty($options['business_url'] ?? '');

    // Vuoto = home della lingua di default: get_option('home') non passa dai filtri di WPML
    // e segue il dominio dopo un search & replace.
    if ($url === '') {
        $url = trailingslashit(get_option('home'));
    }

    return $url;
}

function gsm_get_organization_id($options = null) {
    $options = $options ?? gsm_get_options();

    return untrailingslashit(gsm_get_business_url($options)) . '/#organization';
}

function gsm_get_local_business_id($options = null) {
    $options = $options ?? gsm_get_options();

    return untrailingslashit(gsm_get_business_url($options)) . '/#localbusiness';
}

function gsm_get_logo_id($options = null) {
    $options = $options ?? gsm_get_options();

    return untrailingslashit(gsm_get_business_url($options)) . '/#/schema/logo/image/';
}

function gsm_is_yoast_mode($options = null) {
    $options = $options ?? gsm_get_options();

    return !empty($options['yoast_integration']) && defined('WPSEO_VERSION');
}

function gsm_translate_string($value, $name) {
    return apply_filters('wpml_translate_single_string', $value, 'graffette-schema-manager', $name);
}

function gsm_float_string_or_empty($value) {
    $value = trim((string) $value);
    $value = str_replace(',', '.', $value);

    if ($value === '' || !is_numeric($value)) {
        return '';
    }

    return (string) $value;
}
