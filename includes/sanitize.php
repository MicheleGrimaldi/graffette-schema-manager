<?php
if (!defined('ABSPATH')) {
    exit;
}

function gsm_sanitize_options($input) {
    $defaults = gsm_get_default_options();
    $input = is_array($input) ? $input : [];
    $output = [];

    $output['enabled'] = empty($input['enabled']) ? 0 : 1;

    $output_mode = isset($input['output_mode']) ? sanitize_key($input['output_mode']) : 'automatic';
    $output['output_mode'] = in_array($output_mode, ['automatic', 'shortcode_only'], true) ? $output_mode : 'automatic';

    $output_position = isset($input['output_position']) ? sanitize_key($input['output_position']) : 'head';
    $output['output_position'] = in_array($output_position, ['head', 'footer'], true) ? $output_position : 'head';

    $output['debug_comment'] = empty($input['debug_comment']) ? 0 : 1;
    $output['yoast_integration'] = empty($input['yoast_integration']) ? 0 : 1;

    $output['local_business_enabled'] = empty($input['local_business_enabled']) ? 0 : 1;

    $scope = isset($input['local_business_scope']) ? sanitize_key($input['local_business_scope']) : 'front_page';
    $output['local_business_scope'] = in_array($scope, ['front_page', 'all'], true) ? $scope : 'front_page';

    $business_type = isset($input['business_type']) ? sanitize_text_field($input['business_type']) : 'LocalBusiness';
    $allowed_types = array_keys(gsm_get_business_types());
    $output['business_type'] = in_array($business_type, $allowed_types, true) ? $business_type : 'LocalBusiness';
    $output['business_type_custom'] = isset($input['business_type_custom']) ? sanitize_text_field($input['business_type_custom']) : '';

    $text_fields = [
        'business_name',
        'business_legal_name',
        'business_description',
        'business_telephone',
        'business_email',
        'business_price_range',
        'business_tax_id',
        'business_vat_id',
        'business_currencies',
        'contact_point_type',
        'address_street',
        'address_postal_code',
        'address_city',
        'address_region',
        'address_country',
        'person_name',
        'person_job_title',
        'person_description',
        'person_email',
        'person_telephone',
    ];

    foreach ($text_fields as $field) {
        $output[$field] = isset($input[$field]) ? sanitize_text_field($input[$field]) : ($defaults[$field] ?? '');
    }

    $url_fields = [
        'business_url',
        'business_logo_url',
        'business_image_url',
        'google_maps_url',
        'person_url',
        'person_image_url',
    ];

    foreach ($url_fields as $field) {
        $output[$field] = isset($input[$field]) ? gsm_url_or_empty($input[$field]) : ($defaults[$field] ?? '');
    }

    $output['geo_latitude'] = isset($input['geo_latitude']) ? gsm_float_string_or_empty($input['geo_latitude']) : '';
    $output['geo_longitude'] = isset($input['geo_longitude']) ? gsm_float_string_or_empty($input['geo_longitude']) : '';

    $output['same_as'] = isset($input['same_as']) ? sanitize_textarea_field($input['same_as']) : '';
    $output['business_area_served'] = isset($input['business_area_served']) ? sanitize_textarea_field($input['business_area_served']) : '';
    $output['business_languages'] = isset($input['business_languages']) ? sanitize_textarea_field($input['business_languages']) : '';
    $output['person_same_as'] = isset($input['person_same_as']) ? sanitize_textarea_field($input['person_same_as']) : '';

    $output['opening_hours'] = gsm_sanitize_opening_hours($input['opening_hours'] ?? []);

    $output['person_enabled'] = empty($input['person_enabled']) ? 0 : 1;
    $output['person_works_for_business'] = empty($input['person_works_for_business']) ? 0 : 1;

    return array_replace_recursive($defaults, $output);
}

function gsm_sanitize_opening_hours($input_hours) {
    $input_hours = is_array($input_hours) ? $input_hours : [];
    $days = gsm_get_days();
    $output = [];

    foreach ($days as $day_key => $day_label) {
        $day_input = isset($input_hours[$day_key]) && is_array($input_hours[$day_key]) ? $input_hours[$day_key] : [];
        $closed = empty($day_input['closed']) ? 0 : 1;

        $output[$day_key] = [
            'closed' => $closed,
            'slots' => [
                'morning' => [
                    'opens' => '',
                    'closes' => '',
                ],
                'afternoon' => [
                    'opens' => '',
                    'closes' => '',
                ],
            ],
        ];

        if ($closed) {
            continue;
        }

        foreach (['morning', 'afternoon'] as $slot_key) {
            $slot = $day_input['slots'][$slot_key] ?? [];
            $opens = isset($slot['opens']) ? sanitize_text_field($slot['opens']) : '';
            $closes = isset($slot['closes']) ? sanitize_text_field($slot['closes']) : '';

            if (!gsm_is_valid_time($opens) || !gsm_is_valid_time($closes) || $opens >= $closes) {
                $opens = '';
                $closes = '';
            }

            $output[$day_key]['slots'][$slot_key] = [
                'opens' => $opens,
                'closes' => $closes,
            ];
        }
    }

    return $output;
}
