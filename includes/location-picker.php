<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function sanitize_search_url(string $url): string
{
    $url = trim($url);
    if (strlen($url) > 2000 || preg_match('/[\s<>"\x00-\x1f]/', $url) || str_contains(rawurldecode($url), '..') || str_contains(rawurldecode($url), '\\')) {
        return '';
    }
    $relative = str_starts_with($url, '/') && !str_starts_with($url, '//');
    $probe = $relative ? 'https://example.invalid'.$url : $url;
    $parts = wp_parse_url($probe);
    return filter_var($probe, FILTER_VALIDATE_URL) && ($parts['scheme'] ?? '') === 'https' && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['fragment']) ? $url : '';
}

function coordinate_fields(string $prefix, array $fields): void
{
    $id = sanitize_html_class($prefix.'-picker');
    $settings = settings();
    $empty = $prefix === 'botud_place' ? 'Brak punktu obszaru. Obszar pozostaje w wykazie bez pinezki.' : 'Brak własnego punktu. Jednostka korzysta z przypisanych obszarów.';
    echo '<div data-botud-location-picker data-location-empty="'.esc_attr($empty).'"><p><button type="button" class="button" data-location-open aria-expanded="false" aria-controls="'.$id.'-view">Wybierz miejsce na mapie</button> <button type="button" class="button" data-location-clear>Usuń punkt</button></p>';
    echo '<p class="description">Po otwarciu mapy pobierany jest podkład '.esc_html($settings['provider_name']).'. ';
    if ($settings['search_enabled']) {
        echo 'Kliknięcie Szukaj wysyła wpisany adres lub nazwę miejsca do '.esc_html($settings['search_provider_name']).'. <a href="'.esc_url($settings['search_info_url']).'">Informacje o wyszukiwarce</a>. ';
    }
    echo '<a href="'.esc_url($settings['provider_privacy_url']).'">Prywatność podkładu</a>. Wskazuj tylko miejsca przeznaczone do publikacji.</p><div id="'.$id.'-view" data-location-view hidden>';
    if ($settings['search_enabled']) {
        echo '<p><label for="'.$id.'-search"><strong>Adres lub miejscowość</strong></label><br><input class="widefat" id="'.$id.'-search" type="search" maxlength="200" autocomplete="off" data-location-query placeholder="Wpisz adres lub nazwę miejsca"></p><p><button type="button" class="button" data-location-search>Szukaj</button></p><ul data-location-results aria-label="Wyniki wyszukiwania"></ul>';
    }
    echo '<p class="description">Kliknij na mapie lub przeciągnij pinezkę. Możesz też przesunąć mapę klawiszami strzałek i wybrać jej środek.</p><div data-location-map role="region" aria-label="Wybór miejsca działania na mapie" style="height:320px;width:100%;max-width:100%"></div><p><button type="button" class="button" data-location-center>Ustaw punkt w środku mapy</button></p></div><p role="status" aria-live="polite" data-location-status></p><details data-location-coordinates><summary>Współrzędne (opcjonalna edycja ręczna)</summary>';
    coordinate_inputs($prefix, $fields);
    echo '</details><p class="description">Zmiany miejsca zapiszesz przyciskiem WordPressa: Aktualizuj, Opublikuj lub Dodaj nową pozycję.</p></div>';
}

add_action('admin_enqueue_scripts', static function (): void {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== TYPE || !in_array($screen->base, ['post', 'edit-tags', 'term'], true) || (in_array($screen->base, ['edit-tags', 'term'], true) && $screen->taxonomy !== PLACE)) {
        return;
    }
    $plugin = dirname(__DIR__).'/atlas-jednostek.php';
    wp_enqueue_style('atlas-jednostek-picker-leaflet', plugins_url('assets/vendor/leaflet/leaflet.css', $plugin), [], '1.9.4');
    wp_enqueue_script('atlas-jednostek-picker-leaflet', plugins_url('assets/vendor/leaflet/leaflet.js', $plugin), [], '1.9.4', true);
    wp_enqueue_script('atlas-jednostek-location-picker', plugins_url('assets/location-picker.js', $plugin), ['atlas-jednostek-picker-leaflet'], asset_version('assets/location-picker.js'), true);
    $settings = settings();
    $points = [];
    foreach (get_terms(['taxonomy' => PLACE, 'hide_empty' => false]) as $term) {
        $point = place_coordinates((array) get_term_meta($term->term_id, TERM_META, true));
        if ($point) {
            $points[] = $point;
        }
    }
    wp_localize_script('atlas-jednostek-location-picker', 'botudLocation', [
        'tilesUrl' => $settings['tiles_url'], 'maxZoom' => $settings['max_zoom'],
        'providerAttribution' => esc_html($settings['provider_attribution']),
        'searchUrl' => $settings['search_enabled'] ? $settings['search_url'] : '',
        'points' => $points,
    ]);
});
