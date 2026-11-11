<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Grafo standalone (senza Yoast): LocalBusiness completo + Person.
 */
function gsm_build_schema_graph($ignore_scope = false) {
    $options = gsm_get_options();
    $graph = [];

    if (!empty($options['local_business_enabled']) && ($ignore_scope || gsm_local_business_in_scope($options))) {
        $local_business = gsm_build_local_business_schema($options, true);

        if (!empty($local_business)) {
            $graph[] = $local_business;
        }
    }

    if (!empty($options['person_enabled'])) {
        $person = gsm_build_person_schema($options);

        if (!empty($person)) {
            $graph[] = $person;
        }
    }

    $graph = apply_filters('gsm_schema_graph', $graph, null);

    if (empty($graph)) {
        return [];
    }

    return [
        '@context' => 'https://schema.org',
        '@graph' => array_values($graph),
    ];
}

/**
 * Integrazione Yoast: arricchisce l'Organization di Yoast, ne rende l'@id unico
 * in tutte le lingue e aggiunge LocalBusiness/Person allo stesso grafo.
 */
function gsm_filter_yoast_graph($graph, $context = null) {
    $options = gsm_get_options();

    if (empty($options['enabled']) || !gsm_is_yoast_mode($options) || !is_array($graph)) {
        return $graph;
    }

    $org_id = gsm_get_organization_id($options);
    $logo_id = gsm_get_logo_id($options);
    $id_map = [];
    $org_index = null;

    foreach ($graph as $index => $node) {
        $types = (array) ($node['@type'] ?? []);
        $node_id = (string) ($node['@id'] ?? '');

        if ($org_index === null && in_array('Organization', $types, true) && substr($node_id, -strlen('#organization')) === '#organization') {
            $org_index = $index;
            $id_map[$node_id] = $org_id;

            $yoast_logo_id = (string) ($node['logo']['@id'] ?? '');

            if ($yoast_logo_id !== '') {
                $id_map[$yoast_logo_id] = $logo_id;
            }
        }
    }

    if (!empty($id_map)) {
        $graph = gsm_remap_schema_ids($graph, $id_map);
    }

    if ($org_index !== null) {
        $graph[$org_index] = gsm_enrich_organization_node($graph[$org_index], $options);
    } else {
        $graph[] = gsm_enrich_organization_node([
            '@type' => 'Organization',
            '@id' => $org_id,
            'name' => trim((string) ($options['business_name'] ?? '')),
        ], $options);
    }

    if (!empty($options['local_business_enabled']) && gsm_local_business_in_scope($options)) {
        $local_business = gsm_build_local_business_schema($options, false);

        if (!empty($local_business)) {
            $graph[] = $local_business;
        }
    }

    if (!empty($options['person_enabled'])) {
        $person = gsm_build_person_schema($options);

        if (!empty($person)) {
            $graph[] = $person;
        }
    }

    return array_values(apply_filters('gsm_schema_graph', $graph, $context));
}

/**
 * Anteprima per il pannello Debug in modalità Yoast: i nodi che il plugin aggiunge
 * o arricchisce, ignorando le condizioni di pagina.
 */
function gsm_build_yoast_preview_graph() {
    $options = gsm_get_options();
    $graph = [
        gsm_enrich_organization_node([
            '@type' => 'Organization',
            '@id' => gsm_get_organization_id($options),
            'name' => trim((string) ($options['business_name'] ?? '')),
        ], $options),
    ];

    if (!empty($options['local_business_enabled'])) {
        $graph[] = gsm_build_local_business_schema($options, false);
    }

    if (!empty($options['person_enabled'])) {
        $graph[] = gsm_build_person_schema($options);
    }

    return [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter($graph)),
    ];
}

function gsm_remap_schema_ids($data, $id_map) {
    if (!is_array($data)) {
        return $data;
    }

    foreach ($data as $key => $value) {
        if ($key === '@id' && is_string($value) && isset($id_map[$value])) {
            $data[$key] = $id_map[$value];
        } elseif (is_array($value)) {
            $data[$key] = gsm_remap_schema_ids($value, $id_map);
        }
    }

    return $data;
}

function gsm_local_business_in_scope($options) {
    $scope = $options['local_business_scope'] ?? 'front_page';

    // is_front_page() vale anche per le home tradotte da WPML (/it/, /de/, ...).
    return $scope === 'all' || is_front_page();
}

function gsm_enrich_organization_node($node, $options) {
    $node['url'] = gsm_get_business_url($options);

    $simple_map = [
        'business_legal_name' => 'legalName',
        'business_telephone' => 'telephone',
        'business_email' => 'email',
        'business_vat_id' => 'vatID',
        'business_tax_id' => 'taxID',
    ];

    foreach ($simple_map as $option_key => $schema_key) {
        $value = trim((string) ($options[$option_key] ?? ''));

        if ($value !== '') {
            $node[$schema_key] = $value;
        }
    }

    $description = gsm_get_business_description($options);

    if ($description !== '') {
        $node['description'] = $description;
    }

    $logo = gsm_url_or_empty($options['business_logo_url'] ?? '');

    if ($logo !== '') {
        $node['logo'] = [
            '@type' => 'ImageObject',
            '@id' => gsm_get_logo_id($options),
            'url' => $logo,
            'contentUrl' => $logo,
            'caption' => $node['name'] ?? '',
        ];
        $node['image'] = ['@id' => gsm_get_logo_id($options)];
    }

    $address = gsm_build_postal_address($options);

    if (!empty($address)) {
        $node['address'] = $address;
    }

    $same_as = array_merge(
        (array) ($node['sameAs'] ?? []),
        gsm_textarea_lines_to_array($options['same_as'] ?? '')
    );

    if (!empty($same_as)) {
        $node['sameAs'] = array_values(array_unique($same_as));
    }

    $area_served = gsm_build_area_served($options);

    if (!empty($area_served)) {
        $node['areaServed'] = $area_served;
    }

    $languages = gsm_textarea_lines_to_text_array($options['business_languages'] ?? '');

    if (!empty($languages)) {
        $node['knowsLanguage'] = $languages;
    }

    $contact_point = gsm_build_contact_point($options);

    if (!empty($contact_point)) {
        $node['contactPoint'] = $contact_point;
    }

    return $node;
}

/**
 * $standalone = true: entità autonoma con tutti i dati (sito senza Yoast).
 * $standalone = false: sede fisica collegata all'Organization di Yoast.
 */
function gsm_build_local_business_schema($options, $standalone = true) {
    $name = trim((string) ($options['business_name'] ?? ''));
    $url = gsm_get_business_url($options);

    if ($name === '' || $url === '') {
        return [];
    }

    $business_type = $options['business_type'] ?? 'LocalBusiness';

    if ($business_type === 'Custom') {
        $custom_type = sanitize_text_field($options['business_type_custom'] ?? '');
        $business_type = $custom_type !== '' ? $custom_type : 'LocalBusiness';
    }

    $schema = [
        '@type' => $business_type,
        '@id' => gsm_get_local_business_id($options),
        'name' => $name,
        'url' => $url,
    ];

    if (!$standalone) {
        $schema['parentOrganization'] = ['@id' => gsm_get_organization_id($options)];
    }

    $simple_map = [
        'business_telephone' => 'telephone',
        'business_email' => 'email',
        'business_price_range' => 'priceRange',
        'business_currencies' => 'currenciesAccepted',
    ];

    if ($standalone) {
        $simple_map += [
            'business_legal_name' => 'legalName',
            'business_tax_id' => 'taxID',
            'business_vat_id' => 'vatID',
        ];
    }

    foreach ($simple_map as $option_key => $schema_key) {
        $value = trim((string) ($options[$option_key] ?? ''));

        if ($value !== '') {
            $schema[$schema_key] = $value;
        }
    }

    if ($standalone) {
        $description = gsm_get_business_description($options);

        if ($description !== '') {
            $schema['description'] = $description;
        }
    }

    $logo = gsm_url_or_empty($options['business_logo_url'] ?? '');
    $image = gsm_url_or_empty($options['business_image_url'] ?? '');

    if ($logo !== '' && $standalone) {
        $schema['logo'] = $logo;
    }

    if ($image !== '') {
        $schema['image'] = $image;
    } elseif ($logo !== '') {
        $schema['image'] = $logo;
    }

    $address = gsm_build_postal_address($options);

    if (!empty($address)) {
        $schema['address'] = $address;
    }

    $geo = gsm_build_geo_coordinates($options);

    if (!empty($geo)) {
        $schema['geo'] = $geo;
    }

    $maps_url = gsm_url_or_empty($options['google_maps_url'] ?? '');

    if ($maps_url !== '') {
        $schema['hasMap'] = $maps_url;
    }

    $area_served = gsm_build_area_served($options);

    if (!empty($area_served)) {
        $schema['areaServed'] = $area_served;
    }

    if ($standalone) {
        $same_as = gsm_textarea_lines_to_array($options['same_as'] ?? '');

        if (!empty($same_as)) {
            $schema['sameAs'] = $same_as;
        }

        $languages = gsm_textarea_lines_to_text_array($options['business_languages'] ?? '');

        if (!empty($languages)) {
            $schema['knowsLanguage'] = $languages;
        }

        $contact_point = gsm_build_contact_point($options);

        if (!empty($contact_point)) {
            $schema['contactPoint'] = $contact_point;
        }
    }

    $opening_hours = gsm_build_opening_hours_specification($options['opening_hours'] ?? []);

    if (!empty($opening_hours)) {
        $schema['openingHoursSpecification'] = $opening_hours;
    }

    return $schema;
}

function gsm_get_business_description($options) {
    $description = trim((string) ($options['business_description'] ?? ''));

    if ($description === '') {
        return '';
    }

    return trim((string) gsm_translate_string($description, 'business_description'));
}

/**
 * Una riga per area. Codice ISO a 2 lettere (es. GB) = Country, altrimenti Place.
 */
function gsm_build_area_served($options) {
    $areas = [];

    foreach (gsm_textarea_lines_to_text_array($options['business_area_served'] ?? '') as $line) {
        $areas[] = [
            '@type' => preg_match('/^[A-Z]{2}$/', $line) ? 'Country' : 'Place',
            'name' => $line,
        ];
    }

    return count($areas) === 1 ? $areas[0] : $areas;
}

function gsm_build_contact_point($options) {
    $telephone = trim((string) ($options['business_telephone'] ?? ''));
    $email = trim((string) ($options['business_email'] ?? ''));

    if ($telephone === '' && $email === '') {
        return [];
    }

    $contact_point = [
        '@type' => 'ContactPoint',
        'contactType' => trim((string) ($options['contact_point_type'] ?? '')) ?: 'customer service',
    ];

    if ($telephone !== '') {
        $contact_point['telephone'] = $telephone;
    }

    if ($email !== '') {
        $contact_point['email'] = $email;
    }

    $languages = gsm_textarea_lines_to_text_array($options['business_languages'] ?? '');

    if (!empty($languages)) {
        $contact_point['availableLanguage'] = $languages;
    }

    $area_served = gsm_build_area_served($options);

    if (!empty($area_served)) {
        $contact_point['areaServed'] = $area_served;
    }

    return $contact_point;
}

function gsm_build_postal_address($options) {
    $street = trim((string) ($options['address_street'] ?? ''));
    $postal_code = trim((string) ($options['address_postal_code'] ?? ''));
    $city = trim((string) ($options['address_city'] ?? ''));
    $region = trim((string) ($options['address_region'] ?? ''));
    $country = trim((string) ($options['address_country'] ?? ''));

    if ($street === '' && $postal_code === '' && $city === '' && $region === '' && $country === '') {
        return [];
    }

    $address = ['@type' => 'PostalAddress'];

    if ($street !== '') {
        $address['streetAddress'] = $street;
    }

    if ($postal_code !== '') {
        $address['postalCode'] = $postal_code;
    }

    if ($city !== '') {
        $address['addressLocality'] = $city;
    }

    if ($region !== '') {
        $address['addressRegion'] = $region;
    }

    if ($country !== '') {
        $address['addressCountry'] = $country;
    }

    return $address;
}

function gsm_build_geo_coordinates($options) {
    $lat = gsm_float_string_or_empty($options['geo_latitude'] ?? '');
    $lng = gsm_float_string_or_empty($options['geo_longitude'] ?? '');

    if ($lat === '' || $lng === '') {
        return [];
    }

    return [
        '@type' => 'GeoCoordinates',
        'latitude' => $lat,
        'longitude' => $lng,
    ];
}

/**
 * Raggruppa i giorni con la stessa fascia oraria: lun-ven 08:00-12:00 diventa
 * un solo OpeningHoursSpecification con dayOfWeek come array.
 */
function gsm_build_opening_hours_specification($opening_hours) {
    $opening_hours = is_array($opening_hours) ? $opening_hours : [];
    $schema_days = gsm_get_schema_days();
    $groups = [];

    foreach ($schema_days as $day_key => $schema_day) {
        $day = isset($opening_hours[$day_key]) && is_array($opening_hours[$day_key]) ? $opening_hours[$day_key] : [];

        if (!empty($day['closed'])) {
            continue;
        }

        $slots = isset($day['slots']) && is_array($day['slots']) ? $day['slots'] : [];

        foreach (['morning', 'afternoon'] as $slot_key) {
            $slot = isset($slots[$slot_key]) && is_array($slots[$slot_key]) ? $slots[$slot_key] : [];
            $opens = $slot['opens'] ?? '';
            $closes = $slot['closes'] ?? '';

            if (!gsm_is_valid_time($opens) || !gsm_is_valid_time($closes) || $opens >= $closes) {
                continue;
            }

            $groups[$opens . '-' . $closes]['opens'] = $opens;
            $groups[$opens . '-' . $closes]['closes'] = $closes;
            $groups[$opens . '-' . $closes]['days'][] = $schema_day;
        }
    }

    $specs = [];

    foreach ($groups as $group) {
        $specs[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => count($group['days']) === 1 ? $group['days'][0] : $group['days'],
            'opens' => $group['opens'],
            'closes' => $group['closes'],
        ];
    }

    return $specs;
}

function gsm_build_person_schema($options) {
    $name = trim((string) ($options['person_name'] ?? ''));

    if ($name === '') {
        return [];
    }

    $business_url = gsm_get_business_url($options);
    $person_url = gsm_url_or_empty($options['person_url'] ?? '');
    $base_url = $person_url !== '' ? $person_url : $business_url;

    $schema = [
        '@type' => 'Person',
        '@id' => untrailingslashit($base_url) . '/#person-' . sanitize_title($name),
        'name' => $name,
    ];

    $simple_map = [
        'person_job_title' => 'jobTitle',
        'person_description' => 'description',
        'person_email' => 'email',
        'person_telephone' => 'telephone',
    ];

    foreach ($simple_map as $option_key => $schema_key) {
        $value = trim((string) ($options[$option_key] ?? ''));

        if ($value !== '') {
            $schema[$schema_key] = $value;
        }
    }

    if ($person_url !== '') {
        $schema['url'] = $person_url;
    }

    $image = gsm_url_or_empty($options['person_image_url'] ?? '');

    if ($image !== '') {
        $schema['image'] = $image;
    }

    $same_as = gsm_textarea_lines_to_array($options['person_same_as'] ?? '');

    if (!empty($same_as)) {
        $schema['sameAs'] = $same_as;
    }

    if (!empty($options['person_works_for_business'])) {
        if (gsm_is_yoast_mode($options)) {
            $schema['worksFor'] = ['@id' => gsm_get_organization_id($options)];
        } elseif (!empty($options['local_business_enabled'])) {
            $schema['worksFor'] = ['@id' => gsm_get_local_business_id($options)];
        }
    }

    return $schema;
}
