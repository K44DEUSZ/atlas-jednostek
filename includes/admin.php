<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function input_field(string $name, string $label, string $value, string $type = 'text'): void
{
    echo '<p><label><strong>'.esc_html($label).'</strong><br><input class="widefat" type="'.esc_attr($type).'" name="'.esc_attr($name).'" value="'.esc_attr($value).'"></label></p>';
}

add_action('add_meta_boxes_'.TYPE, static function (): void {
    add_meta_box('botud-unit-data', 'Typ i dane jednostki', __NAMESPACE__.'\\unit_box', TYPE, 'normal', 'high');
    add_meta_box('botud-unit-contact', 'Osoba prowadząca i kontakt', __NAMESPACE__.'\\contact_box', TYPE, 'normal', 'default');
    add_meta_box('botud-unit-location', 'Miejsce i obszar działania', __NAMESPACE__.'\\location_box', TYPE, 'normal', 'default');
});

function unit_box(\WP_Post $post): void
{
    identity_box($post);
}

add_action('save_post_'.TYPE, __NAMESPACE__.'\\save_unit');

function place_fields($term = null): void
{
    $value = $term ? (array)get_term_meta($term->term_id, TERM_META, true) : [];
    $wrapper = $term ? '<tr class="form-field"><th>Położenie i podpis</th><td>' : '<div class="form-field">';
    echo $wrapper;
    wp_nonce_field('botud_save_place', 'botud_place_nonce');
    $point = place_coordinates($value);
    if ($point) {
        $value['latitude'] = (string) $point[0];
        $value['longitude'] = (string) $point[1];
    }
    coordinate_fields('botud_place', $value);
    echo '<details><summary>Opcjonalny import z linku OpenStreetMap</summary>';
    input_field('botud_place[url]', 'Nowy link ze współrzędnymi', '', 'url');
    echo '<p>Gdy pola współrzędnych są puste, możesz zamiast nich wkleić link zawierający #map=... lub mlat/mlon. Bez współrzędnych obszar pozostaje w wykazie, ale nie ma pinezki.</p></details>';
    foreach (['side' => 'Położenie podpisu','mobile_side' => 'Położenie podpisu na telefonie'] as $key => $label) {
        echo '<p><label>'.esc_html($label).'<br><select name="botud_place['.$key.']">';
        foreach (label_sides() as $side => $name) {
            echo '<option value="'.esc_attr($side).'" '.selected($value[$key] ?? 'bottom', $side, false).'>'.esc_html($name).'</option>';
        }
        echo '</select></label></p>';
    }
    echo '<p><label><input type="checkbox" name="botud_place[regional]" value="1" '.checked(!empty($value['regional']), true, false).'> Punkt reprezentuje większy obszar, np. cały powiat</label></p>';
    echo $term ? '</td></tr>' : '</div>';
}

add_action(PLACE.'_add_form_fields', static function () {
    place_fields();
});
add_action(PLACE.'_edit_form_fields', __NAMESPACE__.'\\place_fields');
function save_place(int $id): void
{
    if (!current_user_can('manage_categories') || !has_place_nonce()) {
        return;
    }
    $input = posted_place_fields();
    $clean = sanitize_place($input);
    $error = place_input_error($input);
    if ($error) {
        set_transient('botud_error_'.get_current_user_id(), $error.' Poprzedni punkt pozostał bez zmian.', 60);
        return;
    }
    update_term_meta($id, TERM_META, $clean);
}
add_action('created_'.PLACE, __NAMESPACE__.'\\save_place');
add_action('edited_'.PLACE, __NAMESPACE__.'\\save_place');

function posted_place_fields(): array
{
    return is_array($_POST['botud_place'] ?? null) ? wp_unslash($_POST['botud_place']) : [];
}

function has_place_nonce(): bool
{
    return is_scalar($_POST['botud_place_nonce'] ?? null) && (bool) wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['botud_place_nonce'])), 'botud_save_place');
}

function place_input_error(array $input): string
{
    if ((($input['latitude'] ?? '') !== '' || ($input['longitude'] ?? '') !== '') && !point_coordinates($input['latitude'] ?? '', $input['longitude'] ?? '')) {
        return 'Nie zapisano położenia: wpisz obie poprawne współrzędne.';
    }
    if (!empty($input['url']) && !sanitize_place($input)['url']) {
        return 'Nie zapisano położenia: wklej pełny link OpenStreetMap ze współrzędnymi.';
    }
    return '';
}

add_filter('pre_insert_term', static function ($term, string $taxonomy) {
    if ($taxonomy !== PLACE || !current_user_can('manage_categories') || !has_place_nonce()) {
        return $term;
    }
    $error = place_input_error(posted_place_fields());
    return $error ? new \WP_Error('botud_invalid_point', $error) : $term;
}, 10, 2);

function kind_fields($term = null): void
{
    $color = $term ? get_term_meta($term->term_id, '_botud_color', true) : '#687189';
    echo $term ? '<tr class="form-field"><th>Kolor oznaczenia</th><td>' : '<div class="form-field">';
    wp_nonce_field('botud_save_kind', 'botud_kind_nonce');
    input_field('botud_color', 'Kolor oznaczenia', sanitize_hex_color($color) ?: '#687189', 'color');
    input_field('botud_order', 'Kolejność (mniejsza liczba wyżej)', (string)($term ? get_term_meta($term->term_id, '_botud_order', true) : 0), 'number');
    echo $term ? '</td></tr>' : '</div>';
}
add_action(KIND.'_add_form_fields', static function () {
    kind_fields();
});
add_action(KIND.'_edit_form_fields', __NAMESPACE__.'\\kind_fields');
function save_kind(int $id): void
{
    if (!current_user_can('manage_categories') || !isset($_POST['botud_kind_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['botud_kind_nonce'])), 'botud_save_kind')) {
        return;
    }
    $color = is_scalar($_POST['botud_color'] ?? null) ? sanitize_hex_color(wp_unslash((string)$_POST['botud_color'])) : null;
    update_term_meta($id, '_botud_color', $color ?: '#687189');
    update_term_meta($id, '_botud_order', absint($_POST['botud_order'] ?? 0));
}
add_action('created_'.KIND, __NAMESPACE__.'\\save_kind');
add_action('edited_'.KIND, __NAMESPACE__.'\\save_kind');

add_action('admin_menu', static function (): void {
    add_submenu_page('edit.php?post_type='.TYPE, 'Ustawienia Atlasu Jednostek', 'Ustawienia', 'manage_options', 'botud-settings', __NAMESPACE__.'\\settings_page');
});
add_action('admin_notices', static function (): void {
    $screen = get_current_screen();
    if ($screen && $screen->taxonomy === KIND) {
        echo '<div class="notice notice-info"><p>Ta lista służy do kolorów i kolejności oznaczeń. Typ jednostki, metodyki oraz rodzaj klubu lub kręgu wybierasz w formularzu jednostki.</p></div>';
    }
    $error = get_transient('botud_error_'.get_current_user_id());
    if ($error) {
        delete_transient('botud_error_'.get_current_user_id());
        echo '<div class="notice notice-error"><p>'.esc_html($error).'</p></div>';
    }
});

function settings_page(): void
{
    settings_tabs();
}

add_action('admin_post_botud_save_settings', static function (): void {
    if (!current_user_can('manage_options')) {
        wp_die('Brak uprawnień.', '', ['response' => 403]);
    }
    check_admin_referer('botud_save_settings');
    $old = settings();
    $input = settings_submission($old);
    $clean = $old;
    $preset = sanitize_key(is_scalar($_POST['botud_palette'] ?? null) ? (string)wp_unslash($_POST['botud_palette']) : '');
    if (isset(color_presets()[$preset])) {
        $input = array_replace($input, color_presets()[$preset]['colors']);
        $input['map_palette'] = 'custom';
    }
    $clean = sanitize_settings($input, $old);
    if (!sanitize_tile_url(is_scalar($input['tiles_url'] ?? null) ? (string)$input['tiles_url'] : '')) {
        set_transient('botud_error_'.get_current_user_id(), 'Nie zapisano adresu podkładu. Podaj poprawny adres HTTPS lub ścieżkę w tej witrynie, zawierającą {z}, {x} i {y}. Zachowano poprzedni adres.', 60);
    }
    if (isset($_POST['botud_bounds'])) {
        $bounds = (array)wp_unslash($_POST['botud_bounds'] ?? []);
        $valid_bounds = [];
        foreach (['south','north','west','east'] as $key) {
            if (isset($bounds[$key]) && is_numeric($bounds[$key]) && abs((float)$bounds[$key]) <= (in_array($key, ['south','north'], true) ? 85 : 180)) {
                $valid_bounds[$key] = (float)$bounds[$key];
            }
        }
        if (count($valid_bounds) === 4 && $valid_bounds['south'] < $valid_bounds['north'] && $valid_bounds['west'] < $valid_bounds['east']) {
            $clean['bounds'] = $valid_bounds;
        } elseif (!array_filter($bounds, static fn ($v) => $v !== '')) {
            $clean['bounds'] = [];
        } else {
            set_transient('botud_error_'.get_current_user_id(), 'Granice mapy są nieprawidłowe. Zachowano poprzedni zasięg.', 60);
        }
    }
    if (!empty($_POST['botud_clear_lines'])) {
        $clean['lines'] = [];
    }
    if (!empty($_FILES['botud_lines']['name'])) {
        $file = $_FILES['botud_lines'];
        $lines = [];
        if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= 2097152 && is_uploaded_file($file['tmp_name'])) {
            $lines = sanitize_lines(json_decode((string)file_get_contents($file['tmp_name']), true));
        }
        if ($lines) {
            $clean['lines'] = $lines;
        } else {
            set_transient('botud_error_'.get_current_user_id(), 'Nie zapisano warstwy mapy: plik musi zawierać prawidłowe linie GeoJSON, do 2 MB.', 60);
        }
    }
    update_option(OPTION, $clean, false);
    wp_safe_redirect(admin_url('edit.php?post_type='.TYPE.'&page=botud-settings&tab='.settings_tab().'&updated=1'));
    exit;
});
