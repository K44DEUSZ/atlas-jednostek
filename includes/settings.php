<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function settings_sections(): array
{
    return ['directory' => 'Wykaz', 'appearance' => 'Wygląd', 'map' => 'Mapa', 'advanced' => 'Zaawansowane'];
}

function settings_tab(): string
{
    return organization_choice($_POST['botud_tab'] ?? $_GET['tab'] ?? null, settings_sections(), 'directory');
}

function settings_section_fields(string $tab): array
{
    return match ($tab) {
        'directory' => ['menu_label', 'title', 'intro', 'map_title', 'map_note'],
        'appearance' => ['accent', 'surface', 'card_background', 'radius', 'density', 'layout', 'show_kind_filters', 'expand_groups'],
        'map' => ['road_map', 'map_palette', 'schematic_style', 'canvas_background', 'river_color', 'map_height', 'map_height_mobile', 'show_labels', 'show_counts'],
        'advanced' => ['max_zoom', 'map_label', 'provider_name', 'tiles_url', 'provider_attribution', 'provider_attribution_url', 'provider_privacy_url', 'search_enabled', 'search_url', 'search_provider_name', 'search_info_url'],
        default => [],
    };
}

function settings_submission(array $old): array
{
    $input = $old;
    foreach (appearance_fields() as $key => $field) {
        if ($field['type'] === 'checkbox') {
            $input[$key] = !empty($old[$key]) ? '1' : '0';
        }
    }
    $posted = isset($_POST['botud_settings']) && is_array($_POST['botud_settings']) ? wp_unslash($_POST['botud_settings']) : [];
    foreach (settings_section_fields(settings_tab()) as $key) {
        $input[$key] = $posted[$key] ?? ((appearance_fields()[$key]['type'] ?? '') === 'checkbox' ? '0' : ($old[$key] ?? ''));
    }
    return $input;
}

function setting_control(string $key, string $label, $value, string $type = 'text', array $choices = []): void
{
    $id = 'botud-setting-'.$key;
    echo '<tr><th scope="row"><label for="'.$id.'">'.esc_html($label).'</label></th><td>';
    $name = 'botud_settings['.$key.']';
    if ($type === 'select') {
        echo '<select id="'.$id.'" name="'.$name.'">';
        foreach ($choices as $choice => $text) {
            echo '<option value="'.esc_attr((string) $choice).'" '.selected((string) $value, (string) $choice, false).'>'.esc_html($text).'</option>';
        }
        echo '</select>';
    } elseif ($type === 'checkbox') {
        echo '<input type="checkbox" id="'.$id.'" name="'.$name.'" value="1" '.checked(!empty($value), true, false).'>';
    } elseif ($type === 'textarea') {
        echo '<textarea class="large-text" id="'.$id.'" name="'.$name.'" rows="3">'.esc_textarea((string) $value).'</textarea>';
    } else {
        $field = appearance_fields()[$key] ?? [];
        $limits = $type === 'number' ? ' min="'.$field['min'].'" max="'.$field['max'].'" step="1"' : '';
        echo '<input class="'.($type === 'color' || $type === 'number' ? 'small-text' : 'regular-text').'" type="'.$type.'" id="'.$id.'" name="'.$name.'" value="'.esc_attr((string) $value).'"'.$limits.'>';
    }
    echo '</td></tr>';
}

function appearance_control(string $key, array $settings): void
{
    $field = appearance_fields()[$key];
    setting_control($key, $field['label'], $settings[$key], $field['type'], $field['options'] ?? []);
}

function settings_tabs(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $value = settings();
    $tab = settings_tab();
    $base = admin_url('edit.php?post_type='.TYPE.'&page=botud-settings');
    echo '<div class="wrap"><h1>Atlas Jednostek: ustawienia</h1>';
    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Ustawienia zapisane.</p></div>';
    }
    echo '<nav class="nav-tab-wrapper" aria-label="Sekcje ustawień">';
    foreach (settings_sections() as $key => $label) {
        echo '<a class="nav-tab'.($key === $tab ? ' nav-tab-active' : '').'" href="'.esc_url($base.'&tab='.$key).'"'.($key === $tab ? ' aria-current="page"' : '').'>'.$label.'</a>';
    }
    echo '</nav><form method="post" enctype="multipart/form-data" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="botud_save_settings"><input type="hidden" name="botud_tab" value="'.$tab.'">';
    wp_nonce_field('botud_save_settings');
    if ($tab === 'directory') {
        echo '<p>Wstaw <code>[atlas_jednostek]</code> w bloku Krótki kod lub elemencie Shortcode w builderze. Dotychczasowy <code>[bot_unit_directory]</code> nadal działa. Jednostki i ich funkcje edytujesz w „Wszystkie jednostki”.</p><table class="form-table">';
        foreach (['menu_label' => 'Nazwa menu w panelu', 'title' => 'Nagłówek wykazu', 'intro' => 'Wstęp', 'map_title' => 'Nagłówek mapy', 'map_note' => 'Objaśnienie mapy'] as $key => $label) {
            setting_control($key, $label, $value[$key], in_array($key, ['intro', 'map_note'], true) ? 'textarea' : 'text');
        }
        echo '</table>';
    } elseif ($tab === 'appearance') {
        echo '<p>Czcionka jest dziedziczona z motywu. Wybierz gotową paletę albo ustaw własne kolory wykazu.</p><p><label for="botud-palette"><strong>Zastosuj paletę przy zapisie</strong></label><br><select id="botud-palette" name="botud_palette"><option value="">Zachowaj własne kolory</option>';
        foreach (color_presets() as $key => $preset) {
            echo '<option value="'.$key.'">'.$preset['label'].'</option>';
        }
        echo '</select></p><table class="form-table">';
        foreach (settings_section_fields($tab) as $key) {
            appearance_control($key, $value);
        }
        echo '</table>';
    } elseif ($tab === 'map') {
        echo '<p>Schemat działa lokalnie. Dodatkowy podkład OpenStreetMap pobiera się dopiero po kliknięciu „Mapa drogowa” przez odwiedzającego.</p><table class="form-table">';
        setting_control('road_map', 'Dostępny podkład', $value['road_map'] ? '1' : '0', 'select', ['0' => 'Tylko lokalny schemat', '1' => 'Schemat i opcjonalna mapa drogowa OpenStreetMap']);
        setting_control('schematic_style', 'Styl lokalnego schematu', $value['schematic_style'], 'select', ['dots' => 'Delikatna siatka punktów', 'plain' => 'Gładkie tło']);
        setting_control('map_palette', 'Kolorystyka schematu', $value['map_palette'], 'select', ['custom' => 'Własne kolory', 'navy' => 'Jasna', 'forest' => 'Leśna', 'warm' => 'Ciepła']);
        echo '</table><div data-botud-custom-map><table class="form-table">';
        appearance_control('canvas_background', $value);
        appearance_control('river_color', $value);
        echo '</table></div><table class="form-table">';
        foreach (['map_height', 'map_height_mobile', 'show_labels', 'show_counts'] as $key) {
            appearance_control($key, $value);
        }
        echo '</table><p>Układ „mapa nad wykazem”, „obok wykazu” lub „sam wykaz” wybierzesz w sekcji <a href="'.esc_url($base.'&tab=appearance').'">Wygląd</a>.</p>';
    } else {
        echo '<p>Te ustawienia nie są potrzebne przy zwykłym dodawaniu jednostek. Dane dostawcy zmieniaj zgodnie z jego warunkami i aktualizuj informację o prywatności.</p><table class="form-table">';
        appearance_control('max_zoom', $value);
        foreach (['map_label' => 'Napis w tle schematu', 'provider_name' => 'Nazwa dostawcy podkładu', 'tiles_url' => 'Adres kafli z polami {z}, {x}, {y}', 'provider_attribution' => 'Dodatkowe oznaczenie dostawcy', 'provider_attribution_url' => 'Link oznaczenia (HTTPS)', 'provider_privacy_url' => 'Polityka prywatności dostawcy (HTTPS)'] as $key => $label) {
            setting_control($key, $label, $value[$key]);
        }
        echo '</table><p>Domyślnie OpenStreetMap. Oznaczenie jego autorów pozostaje widoczne. Podkład korzysta z publicznego adresu HTTPS lub własnej ścieżki, bez tajnych kluczy. Nie pobiera obszarów na zapas. <a href="https://operations.osmfoundation.org/policies/tiles/">Zasady korzystania z podkładu</a>.</p><h2>Wyszukiwanie miejsca w panelu</h2><p>Działa wyłącznie podczas edycji jednostek i obszarów, po kliknięciu Szukaj. Domyślnie używa Photon. Własny serwer musi obsługiwać jego API i dostęp z przeglądarki (CORS). Publiczny serwer demonstracyjny ma ograniczoną dostępność; przy większym ruchu wybierz własnego dostawcę. Nie wpisuj tajnych kluczy.</p><table class="form-table">';
        appearance_control('search_enabled', $value);
        foreach (['search_provider_name' => 'Nazwa wyszukiwarki', 'search_url' => 'Adres API Photon (HTTPS lub ścieżka lokalna)', 'search_info_url' => 'Informacje i zasady dostawcy (HTTPS)'] as $key => $label) {
            setting_control($key, $label, $value[$key]);
        }
        echo '</table><details><summary>Ręczny zasięg i lokalna warstwa linii</summary><p>Zostaw puste granice, aby zasięg dopasowywał się automatycznie do wszystkich punktów.</p>';
        foreach (['south' => 'Południe', 'north' => 'Północ', 'west' => 'Zachód', 'east' => 'Wschód'] as $key => $label) {
            input_field('botud_bounds['.$key.']', $label, (string) ($value['bounds'][$key] ?? ''));
        }
        echo '<p><label>Opcjonalna warstwa linii GeoJSON (LineString / MultiLineString), do 2 MB<br><input type="file" name="botud_lines" accept=".json,.geojson,application/geo+json,application/json"></label></p><p><label><input type="checkbox" name="botud_clear_lines" value="1"> Usuń obecną warstwę linii</label></p></details>';
    }
    submit_button();
    echo '</form><p>Atlas Jednostek '.esc_html(VERSION).'. Autor: <a href="https://kaadeusz.pl/">kaadeusz.pl</a>. Dezaktywacja zachowuje dane jednostek i ustawienia.</p></div>';
}
