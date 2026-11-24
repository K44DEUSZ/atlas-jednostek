<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function organization_select(string $key, string $label, array $choices, string $value, bool $empty = true): void
{
    echo '<p><label for="botud-'.$key.'"><strong>'.esc_html($label).'</strong></label><br><select class="widefat" id="botud-'.$key.'" name="botud_unit['.$key.']">';
    if ($empty) {
        echo '<option value="">Wybierz</option>';
    }
    foreach ($choices as $key_value => $text) {
        echo '<option value="'.esc_attr($key_value).'" '.selected($value, $key_value, false).'>'.esc_html($text).'</option>';
    }
    echo '</select></p>';
}

function identity_box(\WP_Post $post): void
{
    $fields = unit_data($post->ID);
    wp_nonce_field('botud_save_unit', 'botud_nonce');
    echo '<p>Wybierz typ jednostki. Formularz dopasuje metodyki, rodzaj i funkcję osoby prowadzącej. Szkice pozostają poza publicznym wykazem.</p>';
    organization_select('unit_type', 'Typ jednostki', unit_types(), $fields['unit_type'] ?? '');
    input_field('botud_unit[number]', 'Numer / identyfikator (opcjonalny)', (string) ($fields['number'] ?? ''));
    echo '<p class="description">Numer może pozostać pusty. Pełna nazwa jest tytułem nad opisem; numer już obecny na jej początku nie będzie powtarzany.</p>';
    echo '<fieldset data-botud-types="troop"><legend><strong>Metodyki drużyny</strong></legend><p>Zaznacz wszystkie stosowane metodyki. Co najmniej dwie oznaczają drużynę wielopoziomową. Sama metodyka zuchowa oznacza Gromadę Zuchową.</p>';
    foreach (methodologies() as $key => $label) {
        echo '<p><label><input type="checkbox" name="botud_unit[methodologies][]" value="'.$key.'" '.checked(in_array($key, $fields['methodologies'] ?? [], true), true, false).'> '.esc_html($label).'</label></p>';
    }
    if (!empty($fields['legacy_multilevel'])) {
        echo '<div class="notice notice-warning inline"><p>Ta drużyna była wcześniej oznaczona jako wielopoziomowa. Uzupełnij rzeczywiste metodyki; dotychczasowe oznaczenie pozostaje do czasu ich wskazania.</p></div>';
    }
    echo '</fieldset><p data-botud-types="pack">Gromada Zuchowa ma automatycznie przypisaną metodykę zuchową.</p>';
    echo '<div data-botud-types="club">';
    organization_select('club_type', 'Rodzaj klubu', club_types(), $fields['club_type'] ?? '');
    echo '</div><div data-botud-types="circle">';
    organization_select('circle_type', 'Rodzaj kręgu', circle_types(), $fields['circle_type'] ?? '');
    echo '</div><div data-botud-types="club troop">';
    organization_select('specialty', 'Specjalność (dla klubu wymagana)', specialties(), $fields['specialty'] ?? '');
    echo '<div data-botud-specialty="other">';
    input_field('botud_unit[specialty_name]', 'Nazwa innej zatwierdzonej specjalności', (string) ($fields['specialty_name'] ?? ''));
    echo '</div></div><p><strong>Oznaczenie jednostki: </strong><output id="botud-type-preview">'.esc_html(unit_type_label($fields)).'</output></p>';
    input_field('botud_unit[age]', 'Wiek uczestników (opcjonalny)', (string) ($fields['age'] ?? ''));
}

function contact_box(\WP_Post $post): void
{
    $fields = unit_data($post->ID);
    organization_select('leader_gender', 'Forma funkcji', ['neutral' => 'Bez wskazania formy', 'female' => 'Żeńska', 'male' => 'Męska'], $fields['leader_gender'] ?? 'neutral', false);
    echo '<div data-botud-types="circle club">';
    organization_select('leader_role', 'Wariant nazwy funkcji', leader_roles($fields['unit_type'] ?? ''), $fields['leader_role'] ?? '', false);
    echo '</div><p><strong>Funkcja: </strong><output id="botud-role-preview">'.esc_html(leader_title($fields)).'</output></p>';
    echo '<p class="description">Funkcja wynika z typu jednostki. W kręgu lub klubie możesz wybrać dozwolony wariant nazwy. Poniżej wpisz tylko stopień oraz imię i nazwisko.</p>';
    foreach (['leader', 'email', 'phone', 'facebook', 'instagram', 'website'] as $key) {
        $type = $key === 'email' ? 'email' : ($key === 'phone' ? 'tel' : (in_array($key, ['facebook', 'instagram', 'website'], true) ? 'url' : 'text'));
        input_field('botud_unit['.$key.']', unit_fields()[$key], (string) ($fields[$key] ?? ''), $type);
    }
    echo '<p class="description">Przycisk kontaktu i podpis funkcji powstają automatycznie. Puste kontakty nie są publikowane. Zdjęcie lub znak jednostki wybierzesz w bocznym panelu biblioteki WordPressa.</p>';
}

function coordinate_inputs(string $prefix, array $fields): void
{
    foreach (['latitude' => ['Szerokość geograficzna', -85, 85, '52.2297'], 'longitude' => ['Długość geograficzna', -180, 180, '21.0122']] as $key => $field) {
        $id = sanitize_html_class($prefix.'-'.$key);
        echo '<p><label for="'.$id.'"><strong>'.$field[0].'</strong></label><br><input id="'.$id.'" type="number" step="any" min="'.$field[1].'" max="'.$field[2].'" name="'.$prefix.'['.$key.']" value="'.esc_attr((string) ($fields[$key] ?? '')).'" placeholder="'.$field[3].'"></p>';
    }
    echo '<p class="description">Współrzędne WGS84, kolejno szerokość i długość. Wpisz obie wartości albo pozostaw obie puste. Nie pobieramy lokalizacji użytkownika.</p>';
}

function location_box(\WP_Post $post): void
{
    $fields = unit_data($post->ID);
    $selected = wp_get_object_terms($post->ID, PLACE, ['fields' => 'ids']);
    // The passive panel keeps native styling without starting WordPress taxonomy scripts.
    echo '<fieldset><legend><strong>Obszary i miejscowości</strong></legend><div class="wp-tab-panel"><ul class="categorychecklist">';
    foreach (get_terms(['taxonomy' => PLACE, 'hide_empty' => false]) as $term) {
        echo '<li><label><input type="checkbox" name="botud_places[]" value="'.$term->term_id.'" '.checked(in_array($term->term_id, $selected, true), true, false).'> '.esc_html($term->name).'</label></li>';
    }
    echo '</ul></div><p class="description">Możesz wybrać kilka obszarów. Nowe dodasz w podmenu „Obszary i miejscowości”.</p></fieldset>';
    input_field('botud_unit[area]', unit_fields()['area'], (string) ($fields['area'] ?? ''));
    echo '<h3>Własny punkt miejsca działania</h3><p>Opcjonalne współrzędne tworzą osobną pinezkę jednostki. Bez nich mapa korzysta z punktów przypisanych obszarów. Publikuj wyłącznie miejsce przeznaczone do publicznego wskazania.</p>';
    input_field('botud_unit[location_label]', unit_fields()['location_label'], (string) ($fields['location_label'] ?? ''));
    coordinate_fields('botud_unit', $fields);
}

add_filter('enter_title_here', static function (string $placeholder, \WP_Post $post): string {
    return $post->post_type === TYPE ? 'Pełna nazwa jednostki' : $placeholder;
}, 10, 2);

add_action('admin_enqueue_scripts', static function (): void {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== TYPE) {
        return;
    }
    $file = dirname(__DIR__).'/atlas-jednostek.php';
    wp_enqueue_script('atlas-jednostek-admin', plugins_url('assets/admin.js', $file), [], ASSET_VERSION, true);
    $roles = [];
    foreach (unit_types() as $type => $unused) {
        foreach (leader_roles($type) as $role => $label) {
            foreach (['neutral', 'female', 'male'] as $gender) {
                $roles[$type][$role][$gender] = leader_title(['unit_type' => $type, 'leader_role' => $role, 'leader_gender' => $gender]);
            }
        }
    }
    wp_localize_script('atlas-jednostek-admin', 'botudEditor', ['roles' => $roles, 'roleChoices' => array_combine(array_keys(unit_types()), array_map(__NAMESPACE__.'\\leader_roles', array_keys(unit_types()))), 'types' => unit_types(), 'troopLabels' => ['cub' => 'Gromada Zuchowa', 'scout' => 'Drużyna Harcerska', 'older_scout' => 'Drużyna Starszoharcerska', 'rover' => 'Drużyna Wędrownicza'], 'legacyMultilevel' => isset($_GET['post']) && !empty(unit_data(absint($_GET['post']))['legacy_multilevel'])]);
});

function posted_unit_fields(): array
{
    return isset($_POST['botud_unit']) && is_array($_POST['botud_unit']) ? wp_unslash($_POST['botud_unit']) : [];
}

function has_unit_nonce(): bool
{
    return is_scalar($_POST['botud_nonce'] ?? null) && (bool) wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['botud_nonce'])), 'botud_save_unit');
}

add_filter('wp_insert_post_data', static function (array $data, array $postarr): array {
    if (($data['post_type'] ?? '') !== TYPE || !in_array($data['post_status'], ['publish', 'future'], true)) {
        return $data;
    }
    $id = absint($postarr['ID'] ?? 0);
    if (($id && !current_user_can('edit_post', $id)) || (!$id && !current_user_can('edit_posts'))) {
        return $data;
    }
    $raw = has_unit_nonce() ? posted_unit_fields() : ($id ? unit_data($id) : []);
    $clean = sanitize_unit($raw, $id ? unit_data($id) : []);
    $errors = unit_input_errors($raw, $clean, true);
    if ($errors) {
        $old_status = $id ? get_post_status($id) : '';
        $data['post_status'] = in_array($old_status, ['publish', 'future'], true) ? $old_status : 'draft';
        set_transient('botud_error_'.get_current_user_id(), 'Nie opublikowano nowych danych jednostki. '.implode(' ', $errors), 60);
    }
    return $data;
}, 20, 2);

function save_unit(int $id): void
{
    if (wp_is_post_revision($id) || wp_is_post_autosave($id) || !current_user_can('edit_post', $id) || !has_unit_nonce()) {
        return;
    }
    $raw = posted_unit_fields();
    $clean = sanitize_unit($raw, unit_data($id));
    $errors = unit_input_errors($raw, $clean, in_array(get_post_status($id), ['publish', 'future'], true));
    if ($errors) {
        set_transient('botud_error_'.get_current_user_id(), 'Nie zapisano danych jednostki; zachowano poprzednie. '.implode(' ', $errors), 60);
        return;
    }
    update_post_meta($id, META, $clean);
    $kind = classification_term(unit_classification($clean));
    wp_set_object_terms($id, $kind ? [$kind->term_id] : [], KIND);
    $places = array_filter(array_map('absint', (array) ($_POST['botud_places'] ?? [])), static fn ($term) => term_exists($term, PLACE));
    wp_set_object_terms($id, array_values($places), PLACE);
}
