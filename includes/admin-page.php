<?php
if (!defined('ABSPATH')) {
    exit;
}

function gsm_boot_admin_page() {
    add_action('admin_menu', 'gsm_register_admin_menu');
    add_action('admin_init', 'gsm_register_settings');
    add_action('admin_enqueue_scripts', 'gsm_enqueue_admin_assets');
}

function gsm_register_admin_menu() {
    add_options_page(
        __('Graffette Schema Manager', 'graffette-schema-manager'),
        __('Schema Graffette', 'graffette-schema-manager'),
        'manage_options',
        'graffette-schema-manager',
        'gsm_render_admin_page'
    );
}

function gsm_register_settings() {
    register_setting(
        'graffette_schema_settings_group',
        'graffette_schema_options',
        [
            'type' => 'array',
            'sanitize_callback' => 'gsm_sanitize_options',
            'default' => gsm_get_default_options(),
        ]
    );
}

function gsm_enqueue_admin_assets($hook) {
    if ($hook !== 'settings_page_graffette-schema-manager') {
        return;
    }

    wp_enqueue_style(
        'gsm-admin',
        GSM_PLUGIN_URL . 'assets/admin/admin.css',
        [],
        GSM_VERSION
    );

    wp_enqueue_script(
        'gsm-admin',
        GSM_PLUGIN_URL . 'assets/admin/admin.js',
        [],
        GSM_VERSION,
        true
    );
}

function gsm_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = gsm_get_options();
    ?>
    <div class="wrap gsm-wrap">
        <h1><?php esc_html_e('Graffette Schema Manager', 'graffette-schema-manager'); ?></h1>
        <p class="description">
            <?php esc_html_e('Genera JSON-LD per LocalBusiness e Person senza duplicare gli schema già gestiti dai plugin SEO.', 'graffette-schema-manager'); ?>
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('graffette_schema_settings_group'); ?>

            <div class="gsm-tabs" role="tablist">
                <button type="button" class="button gsm-tab is-active" data-target="general"><?php esc_html_e('Generale', 'graffette-schema-manager'); ?></button>
                <button type="button" class="button gsm-tab" data-target="business"><?php esc_html_e('Local Business', 'graffette-schema-manager'); ?></button>
                <button type="button" class="button gsm-tab" data-target="hours"><?php esc_html_e('Orari', 'graffette-schema-manager'); ?></button>
                <button type="button" class="button gsm-tab" data-target="person"><?php esc_html_e('Person', 'graffette-schema-manager'); ?></button>
                <button type="button" class="button gsm-tab" data-target="debug"><?php esc_html_e('Debug', 'graffette-schema-manager'); ?></button>
            </div>

            <div class="gsm-panel is-active" data-panel="general">
                <?php gsm_render_general_fields($options); ?>
            </div>

            <div class="gsm-panel" data-panel="business">
                <?php gsm_render_business_fields($options); ?>
            </div>

            <div class="gsm-panel" data-panel="hours">
                <?php gsm_render_hours_fields($options); ?>
            </div>

            <div class="gsm-panel" data-panel="person">
                <?php gsm_render_person_fields($options); ?>
            </div>

            <div class="gsm-panel" data-panel="debug">
                <?php gsm_render_debug_panel($options); ?>
            </div>

            <?php submit_button(__('Salva impostazioni', 'graffette-schema-manager')); ?>
        </form>
    </div>
    <?php
}

function gsm_field_name($key) {
    return 'graffette_schema_options[' . esc_attr($key) . ']';
}

function gsm_text_input($key, $label, $options, $type = 'text', $description = '') {
    $value = isset($options[$key]) ? (string) $options[$key] : '';
    ?>
    <tr>
        <th scope="row"><label for="gsm-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
        <td>
            <input class="regular-text" type="<?php echo esc_attr($type); ?>" id="gsm-<?php echo esc_attr($key); ?>" name="<?php echo gsm_field_name($key); ?>" value="<?php echo esc_attr($value); ?>">
            <?php if ($description !== '') : ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}

function gsm_textarea_input($key, $label, $options, $description = '') {
    $value = isset($options[$key]) ? (string) $options[$key] : '';
    ?>
    <tr>
        <th scope="row"><label for="gsm-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
        <td>
            <textarea class="large-text" rows="5" id="gsm-<?php echo esc_attr($key); ?>" name="<?php echo gsm_field_name($key); ?>"><?php echo esc_textarea($value); ?></textarea>
            <?php if ($description !== '') : ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}

function gsm_checkbox_input($key, $label, $options, $description = '') {
    $checked = !empty($options[$key]);
    ?>
    <tr>
        <th scope="row"><?php echo esc_html($label); ?></th>
        <td>
            <label>
                <input type="checkbox" name="<?php echo gsm_field_name($key); ?>" value="1" <?php checked($checked); ?>>
                <?php echo esc_html($description !== '' ? $description : $label); ?>
            </label>
        </td>
    </tr>
    <?php
}

function gsm_render_general_fields($options) {
    ?>
    <h2><?php esc_html_e('Output JSON-LD', 'graffette-schema-manager'); ?></h2>
    <table class="form-table" role="presentation">
        <?php gsm_checkbox_input('enabled', __('Attiva output schema', 'graffette-schema-manager'), $options, __('Abilita la generazione del JSON-LD sul frontend.', 'graffette-schema-manager')); ?>
        <tr>
            <th scope="row"><?php esc_html_e('Modalità output', 'graffette-schema-manager'); ?></th>
            <td>
                <label>
                    <input type="radio" name="<?php echo gsm_field_name('output_mode'); ?>" value="automatic" <?php checked($options['output_mode'], 'automatic'); ?>>
                    <?php esc_html_e('Automatico', 'graffette-schema-manager'); ?>
                </label><br>
                <label>
                    <input type="radio" name="<?php echo gsm_field_name('output_mode'); ?>" value="shortcode_only" <?php checked($options['output_mode'], 'shortcode_only'); ?>>
                    <?php esc_html_e('Solo shortcode / funzione PHP', 'graffette-schema-manager'); ?>
                </label>
                <p class="description"><?php esc_html_e('Shortcode disponibile: [graffette_schema]. Funzione PHP: graffette_schema().', 'graffette-schema-manager'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Posizione output automatico', 'graffette-schema-manager'); ?></th>
            <td>
                <label>
                    <input type="radio" name="<?php echo gsm_field_name('output_position'); ?>" value="head" <?php checked($options['output_position'], 'head'); ?>>
                    wp_head
                </label><br>
                <label>
                    <input type="radio" name="<?php echo gsm_field_name('output_position'); ?>" value="footer" <?php checked($options['output_position'], 'footer'); ?>>
                    wp_footer
                </label>
                <p class="description"><?php esc_html_e('Consigliato: wp_head. Il footer resta utile per casi particolari o inserimento tardivo.', 'graffette-schema-manager'); ?></p>
            </td>
        </tr>
        <?php gsm_checkbox_input('debug_comment', __('Commento debug', 'graffette-schema-manager'), $options, __('Mostra un commento HTML per admin loggati prima dello script JSON-LD.', 'graffette-schema-manager')); ?>
        <?php gsm_checkbox_input('yoast_integration', __('Integrazione Yoast', 'graffette-schema-manager'), $options, __('Se Yoast è attivo, arricchisce la sua Organization e aggiunge i nodi al suo grafo invece di stampare un secondo script.', 'graffette-schema-manager')); ?>
        <tr>
            <th scope="row"><?php esc_html_e('Stato Yoast', 'graffette-schema-manager'); ?></th>
            <td>
                <?php if (defined('WPSEO_VERSION')) : ?>
                    <?php echo esc_html(sprintf(__('Yoast SEO %s attivo.', 'graffette-schema-manager'), WPSEO_VERSION)); ?>
                <?php else : ?>
                    <?php esc_html_e('Yoast SEO non attivo: output standalone.', 'graffette-schema-manager'); ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}

function gsm_render_business_fields($options) {
    $types = gsm_get_business_types();
    ?>
    <h2><?php esc_html_e('Local Business', 'graffette-schema-manager'); ?></h2>
    <table class="form-table" role="presentation">
        <?php gsm_checkbox_input('local_business_enabled', __('Attiva LocalBusiness', 'graffette-schema-manager'), $options, __('Genera l’entità LocalBusiness o un suo sottotipo.', 'graffette-schema-manager')); ?>
        <tr>
            <th scope="row"><?php esc_html_e('Pagine LocalBusiness', 'graffette-schema-manager'); ?></th>
            <td>
                <label>
                    <input type="radio" name="<?php echo gsm_field_name('local_business_scope'); ?>" value="front_page" <?php checked($options['local_business_scope'], 'front_page'); ?>>
                    <?php esc_html_e('Solo homepage (anche quelle tradotte)', 'graffette-schema-manager'); ?>
                </label><br>
                <label>
                    <input type="radio" name="<?php echo gsm_field_name('local_business_scope'); ?>" value="all" <?php checked($options['local_business_scope'], 'all'); ?>>
                    <?php esc_html_e('Tutte le pagine', 'graffette-schema-manager'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="gsm-business_type"><?php esc_html_e('Tipo attività', 'graffette-schema-manager'); ?></label></th>
            <td>
                <select id="gsm-business_type" name="<?php echo gsm_field_name('business_type'); ?>">
                    <?php foreach ($types as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($options['business_type'], $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Usa Custom solo quando serve un tipo schema.org non presente in lista.', 'graffette-schema-manager'); ?></p>
            </td>
        </tr>
        <?php gsm_text_input('business_type_custom', __('Tipo custom schema.org', 'graffette-schema-manager'), $options, 'text', __('Esempio: HVACBusiness. Usato solo se Tipo attività = Custom.', 'graffette-schema-manager')); ?>
        <?php gsm_text_input('business_name', __('Nome attività', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('business_legal_name', __('Ragione sociale', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('business_url', __('URL sito', 'graffette-schema-manager'), $options, 'url', __('Lascia vuoto per usare la home della lingua principale: resta corretto anche dopo un cambio di dominio.', 'graffette-schema-manager')); ?>
        <?php gsm_textarea_input('business_description', __('Descrizione breve', 'graffette-schema-manager'), $options, __('Con WPML si traduce in Traduzione stringhe, dominio graffette-schema-manager.', 'graffette-schema-manager')); ?>
        <?php gsm_text_input('business_logo_url', __('URL logo', 'graffette-schema-manager'), $options, 'url'); ?>
        <?php gsm_text_input('business_image_url', __('URL immagine principale', 'graffette-schema-manager'), $options, 'url'); ?>
        <?php gsm_text_input('business_telephone', __('Telefono', 'graffette-schema-manager'), $options, 'tel'); ?>
        <?php gsm_text_input('business_email', __('Email', 'graffette-schema-manager'), $options, 'email'); ?>
        <?php gsm_text_input('business_price_range', __('Price range', 'graffette-schema-manager'), $options, 'text', __('Esempio: €, €€, $$$.', 'graffette-schema-manager')); ?>
        <?php gsm_text_input('business_currencies', __('Valute accettate', 'graffette-schema-manager'), $options, 'text', __('Codici ISO 4217 separati da virgola. Esempio: EUR.', 'graffette-schema-manager')); ?>
        <?php gsm_textarea_input('business_area_served', __('Area servita', 'graffette-schema-manager'), $options, __('Una per riga. Codice paese ISO a 2 lettere (es. GB) = Country, altro testo (es. European Union) = Place.', 'graffette-schema-manager')); ?>
        <?php gsm_textarea_input('business_languages', __('Lingue parlate', 'graffette-schema-manager'), $options, __('Codici BCP 47, una per riga (es. en, it). Lingue in cui l’azienda risponde ai clienti, non quelle del sito.', 'graffette-schema-manager')); ?>
        <?php gsm_text_input('contact_point_type', __('Tipo contatto', 'graffette-schema-manager'), $options, 'text', __('contactType del ContactPoint. Esempio: customer service, sales.', 'graffette-schema-manager')); ?>
        <?php gsm_text_input('business_vat_id', __('Partita IVA (vatID)', 'graffette-schema-manager'), $options, 'text', __('Con prefisso paese. Esempio: IT01234567890.', 'graffette-schema-manager')); ?>
        <?php gsm_text_input('business_tax_id', __('Codice fiscale / Tax ID', 'graffette-schema-manager'), $options, 'text', __('Solo se diverso dalla partita IVA.', 'graffette-schema-manager')); ?>
    </table>

    <h2><?php esc_html_e('Indirizzo e coordinate', 'graffette-schema-manager'); ?></h2>
    <table class="form-table" role="presentation">
        <?php gsm_text_input('address_street', __('Via e numero', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('address_postal_code', __('CAP', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('address_city', __('Città', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('address_region', __('Provincia / Regione', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('address_country', __('Paese', 'graffette-schema-manager'), $options, 'text', __('Default consigliato per l’Italia: IT.', 'graffette-schema-manager')); ?>
        <?php gsm_text_input('geo_latitude', __('Latitudine', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('geo_longitude', __('Longitudine', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('google_maps_url', __('URL Google Maps', 'graffette-schema-manager'), $options, 'url'); ?>
        <?php gsm_textarea_input('same_as', __('Profili sameAs', 'graffette-schema-manager'), $options, __('Uno per riga: LinkedIn, Facebook, Instagram, YouTube, ecc.', 'graffette-schema-manager')); ?>
    </table>
    <?php
}

function gsm_render_hours_fields($options) {
    $days = gsm_get_days();
    $opening_hours = is_array($options['opening_hours'] ?? null) ? $options['opening_hours'] : gsm_get_default_opening_hours();
    ?>
    <h2><?php esc_html_e('Orari di apertura', 'graffette-schema-manager'); ?></h2>
    <p class="description"><?php esc_html_e('Ogni fascia valida viene convertita in un oggetto OpeningHoursSpecification. Se un giorno è chiuso, le fasce vengono ignorate.', 'graffette-schema-manager'); ?></p>

    <p>
        <button type="button" class="button gsm-apply-office-preset"><?php esc_html_e('Applica preset ufficio', 'graffette-schema-manager'); ?></button>
        <span class="description"><?php esc_html_e('Lun-Ven 09:00-13:00 / 14:00-18:00, Sab-Dom chiuso.', 'graffette-schema-manager'); ?></span>
    </p>

    <div class="gsm-hours-grid">
        <?php foreach ($days as $day_key => $day_label) :
            $day = $opening_hours[$day_key] ?? [];
            $closed = !empty($day['closed']);
            $slots = is_array($day['slots'] ?? null) ? $day['slots'] : [];
            $morning = is_array($slots['morning'] ?? null) ? $slots['morning'] : ['opens' => '', 'closes' => ''];
            $afternoon = is_array($slots['afternoon'] ?? null) ? $slots['afternoon'] : ['opens' => '', 'closes' => ''];
            ?>
            <div class="gsm-hours-day" data-day="<?php echo esc_attr($day_key); ?>">
                <h3><?php echo esc_html($day_label); ?></h3>

                <label class="gsm-closed-label">
                    <input type="checkbox" name="graffette_schema_options[opening_hours][<?php echo esc_attr($day_key); ?>][closed]" value="1" <?php checked($closed); ?>>
                    <?php esc_html_e('Chiuso', 'graffette-schema-manager'); ?>
                </label>

                <div class="gsm-time-slots">
                    <div class="gsm-time-slot">
                        <strong><?php esc_html_e('Mattina', 'graffette-schema-manager'); ?></strong>
                        <label>
                            <?php esc_html_e('Apre', 'graffette-schema-manager'); ?>
                            <input type="time" name="graffette_schema_options[opening_hours][<?php echo esc_attr($day_key); ?>][slots][morning][opens]" value="<?php echo esc_attr($morning['opens'] ?? ''); ?>">
                        </label>
                        <label>
                            <?php esc_html_e('Chiude', 'graffette-schema-manager'); ?>
                            <input type="time" name="graffette_schema_options[opening_hours][<?php echo esc_attr($day_key); ?>][slots][morning][closes]" value="<?php echo esc_attr($morning['closes'] ?? ''); ?>">
                        </label>
                    </div>

                    <div class="gsm-time-slot">
                        <strong><?php esc_html_e('Pomeriggio', 'graffette-schema-manager'); ?></strong>
                        <label>
                            <?php esc_html_e('Apre', 'graffette-schema-manager'); ?>
                            <input type="time" name="graffette_schema_options[opening_hours][<?php echo esc_attr($day_key); ?>][slots][afternoon][opens]" value="<?php echo esc_attr($afternoon['opens'] ?? ''); ?>">
                        </label>
                        <label>
                            <?php esc_html_e('Chiude', 'graffette-schema-manager'); ?>
                            <input type="time" name="graffette_schema_options[opening_hours][<?php echo esc_attr($day_key); ?>][slots][afternoon][closes]" value="<?php echo esc_attr($afternoon['closes'] ?? ''); ?>">
                        </label>
                    </div>
                </div>

                <div class="gsm-copy-actions">
                    <button type="button" class="button gsm-copy-next"><?php esc_html_e('Copia sui successivi', 'graffette-schema-manager'); ?></button>
                    <button type="button" class="button gsm-copy-weekdays"><?php esc_html_e('Copia lun-ven', 'graffette-schema-manager'); ?></button>
                    <button type="button" class="button gsm-copy-all"><?php esc_html_e('Copia su tutti', 'graffette-schema-manager'); ?></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function gsm_render_person_fields($options) {
    ?>
    <h2><?php esc_html_e('Schema Person', 'graffette-schema-manager'); ?></h2>
    <table class="form-table" role="presentation">
        <?php gsm_checkbox_input('person_enabled', __('Attiva Person', 'graffette-schema-manager'), $options, __('Genera una entità Person collegabile al LocalBusiness.', 'graffette-schema-manager')); ?>
        <?php gsm_text_input('person_name', __('Nome completo', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('person_job_title', __('Job title', 'graffette-schema-manager'), $options); ?>
        <?php gsm_textarea_input('person_description', __('Bio breve', 'graffette-schema-manager'), $options); ?>
        <?php gsm_text_input('person_url', __('URL personale', 'graffette-schema-manager'), $options, 'url'); ?>
        <?php gsm_text_input('person_image_url', __('URL immagine', 'graffette-schema-manager'), $options, 'url'); ?>
        <?php gsm_text_input('person_email', __('Email', 'graffette-schema-manager'), $options, 'email'); ?>
        <?php gsm_text_input('person_telephone', __('Telefono', 'graffette-schema-manager'), $options, 'tel'); ?>
        <?php gsm_textarea_input('person_same_as', __('Profili sameAs persona', 'graffette-schema-manager'), $options, __('Uno per riga.', 'graffette-schema-manager')); ?>
        <?php gsm_checkbox_input('person_works_for_business', __('Collega all’azienda', 'graffette-schema-manager'), $options, __('Aggiunge worksFor verso l’Organization (modalità Yoast) o il LocalBusiness (standalone).', 'graffette-schema-manager')); ?>
    </table>
    <?php
}

function gsm_render_debug_panel($options) {
    $yoast_mode = gsm_is_yoast_mode($options);
    $schema = $yoast_mode ? gsm_build_yoast_preview_graph() : gsm_build_schema_graph(true);
    $json = !empty($schema) ? wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
    ?>
    <h2><?php esc_html_e('Debug JSON-LD', 'graffette-schema-manager'); ?></h2>
    <p>
        <?php if ($yoast_mode) : ?>
            <?php esc_html_e('Modalità Yoast: nodi aggiunti o arricchiti nel grafo di Yoast. L’Organization reale contiene anche i campi di Yoast (logo, nome, social).', 'graffette-schema-manager'); ?>
        <?php else : ?>
            <?php esc_html_e('Anteprima del JSON-LD generato con le impostazioni correnti salvate.', 'graffette-schema-manager'); ?>
        <?php endif; ?>
    </p>
    <textarea class="large-text code gsm-debug-json" rows="24" readonly><?php echo esc_textarea($json); ?></textarea>
    <p>
        <button type="button" class="button gsm-copy-json"><?php esc_html_e('Copia JSON', 'graffette-schema-manager'); ?></button>
    </p>
    <p class="description">
        <?php esc_html_e('Il plugin non genera Review, AggregateRating, WebSite, WebPage, BreadcrumbList, Article, FAQPage o Product.', 'graffette-schema-manager'); ?>
    </p>
    <?php
}
