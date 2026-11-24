<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function defaults(): array
{
    return [
        'menu_label' => 'Atlas Jednostek', 'title' => 'Jednostki', 'intro' => 'Wybierz obszar na mapie lub skorzystaj z filtrów.',
        'map_title' => 'Gdzie działamy?', 'map_label' => '', 'map_note' => 'Punkty wskazują obszary działania, nie adresy spotkań. Szczegóły ustal z osobą prowadzącą jednostkę.',
        'accent' => '#243060', 'bounds' => [], 'lines' => [], 'legacy_hashes' => [],
        'surface' => '#eef0f7', 'canvas_background' => '#f3f6f5', 'river_color' => '#b8d8e5', 'card_background' => '#ffffff',
        'radius' => 12, 'density' => 'comfortable', 'layout' => 'split', 'map_height' => 480, 'map_height_mobile' => 380,
        'max_zoom' => 18, 'road_map' => false, 'show_labels' => true, 'show_counts' => true, 'show_kind_filters' => true, 'expand_groups' => false,
        'tiles_url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', 'provider_name' => 'OpenStreetMap',
        'provider_attribution' => '', 'provider_attribution_url' => '',
        'provider_privacy_url' => 'https://osmfoundation.org/wiki/Privacy_Policy',
        'schematic_style' => 'dots', 'map_palette' => 'custom',
        'search_enabled' => true, 'search_url' => 'https://photon.komoot.io/api/',
        'search_provider_name' => 'Photon (komoot)', 'search_info_url' => 'https://photon.komoot.io/',
    ];
}

function settings(): array
{
    return array_replace(defaults(), (array) get_option(OPTION, []));
}

function appearance_fields(): array
{
    return [
        'accent' => ['label' => 'Kolor akcentu i przycisków', 'type' => 'color'],
        'surface' => ['label' => 'Tło filtrów i oznaczeń', 'type' => 'color'],
        'canvas_background' => ['label' => 'Tło mapy schematycznej', 'type' => 'color'],
        'river_color' => ['label' => 'Kolor linii mapy schematycznej', 'type' => 'color'],
        'card_background' => ['label' => 'Tło kart jednostek', 'type' => 'color'],
        'radius' => ['label' => 'Zaokrąglenie kart i mapy (px)', 'type' => 'number', 'min' => 0, 'max' => 24],
        'density' => ['label' => 'Odstępy', 'type' => 'select', 'options' => ['comfortable' => 'Swobodne', 'compact' => 'Zwarte']],
        'layout' => ['label' => 'Układ mapy i wykazu', 'type' => 'select', 'options' => ['split' => 'Obok siebie na dużym ekranie', 'stacked' => 'Mapa nad wykazem', 'list' => 'Sam wykaz']],
        'map_height' => ['label' => 'Wysokość mapy na komputerze (px)', 'type' => 'number', 'min' => 280, 'max' => 800],
        'map_height_mobile' => ['label' => 'Wysokość mapy na telefonie (px)', 'type' => 'number', 'min' => 280, 'max' => 600],
        'max_zoom' => ['label' => 'Maksymalny poziom przybliżenia', 'type' => 'number', 'min' => 12, 'max' => 19],
        'show_labels' => ['label' => 'Pokazuj nazwy obszarów przy punktach', 'type' => 'checkbox'],
        'show_counts' => ['label' => 'Pokazuj liczby przy punktach', 'type' => 'checkbox'],
        'show_kind_filters' => ['label' => 'Pokazuj przyciski metodyk nad filtrami', 'type' => 'checkbox'],
        'expand_groups' => ['label' => 'Rozwijaj miejscowości także bez wybranego filtra', 'type' => 'checkbox'],
        'road_map' => ['label' => 'Udostępnij opcjonalną mapę drogową', 'type' => 'checkbox'],
        'search_enabled' => ['label' => 'Wyszukiwanie adresów w panelu', 'type' => 'checkbox'],
    ];
}

function color_presets(): array
{
    return [
        'navy' => ['label' => 'Granatowa', 'colors' => ['accent' => '#243060', 'surface' => '#eef0f7', 'canvas_background' => '#f3f6f5', 'river_color' => '#b8d8e5', 'card_background' => '#ffffff']],
        'forest' => ['label' => 'Leśna', 'colors' => ['accent' => '#245841', 'surface' => '#edf4ef', 'canvas_background' => '#f5f7ee', 'river_color' => '#a9cedd', 'card_background' => '#ffffff']],
        'warm' => ['label' => 'Ciepła', 'colors' => ['accent' => '#8b432a', 'surface' => '#faf0e8', 'canvas_background' => '#faf7ef', 'river_color' => '#b6d6e1', 'card_background' => '#ffffff']],
    ];
}

function sanitize_tile_url(string $url): string
{
    $url = trim($url);
    if (strlen($url) > 2000 || preg_match('/[\s<>"\x00-\x1f]/', $url)) {
        return '';
    }
    foreach (['{z}', '{x}', '{y}'] as $placeholder) {
        if (!str_contains($url, $placeholder)) {
            return '';
        }
    }
    $probe = str_replace(['{z}', '{x}', '{y}', '{s}', '{r}'], ['10', '500', '500', 'a', ''], $url);
    if (strpbrk($probe, '{}')) {
        return '';
    }
    if (str_starts_with($probe, '/') && !str_starts_with($probe, '//')) {
        if (str_contains(rawurldecode($probe), '..') || str_contains(rawurldecode($probe), '\\')) {
            return '';
        }
        return filter_var('https://example.invalid'.$probe, FILTER_VALIDATE_URL) ? $url : '';
    }
    $parts = wp_parse_url($probe);
    if (!filter_var($probe, FILTER_VALIDATE_URL) || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || strpbrk($probe, '{}')) {
        return '';
    }
    return $url;
}

function sanitize_settings(array $input, array $previous): array
{
    $clean = $previous;
    $default = defaults();
    foreach (['menu_label', 'title', 'intro', 'map_title', 'map_label', 'map_note', 'provider_name', 'provider_attribution', 'search_provider_name'] as $key) {
        $clean[$key] = mb_substr(sanitize_text_field(is_scalar($input[$key] ?? null) ? (string) $input[$key] : ''), 0, 1500);
    }
    $clean['menu_label'] = $clean['menu_label'] ?: $default['menu_label'];
    foreach (appearance_fields() as $key => $field) {
        $value = is_scalar($input[$key] ?? null) ? (string) $input[$key] : '';
        if ($field['type'] === 'checkbox') {
            $clean[$key] = $value === '1';
        } elseif ($field['type'] === 'color') {
            $clean[$key] = sanitize_hex_color($value) ?: $default[$key];
        } elseif ($field['type'] === 'number') {
            $clean[$key] = is_numeric($value) ? max($field['min'], min($field['max'], (int) $value)) : $default[$key];
        } elseif ($field['type'] === 'select') {
            $clean[$key] = isset($field['options'][$value]) ? $value : $default[$key];
        }
    }
    $clean['tiles_url'] = sanitize_tile_url(is_scalar($input['tiles_url'] ?? null) ? (string) $input['tiles_url'] : '') ?: $previous['tiles_url'];
    $clean['provider_name'] = $clean['provider_name'] ?: $default['provider_name'];
    $clean['provider_attribution_url'] = esc_url_raw(is_scalar($input['provider_attribution_url'] ?? null) ? (string) $input['provider_attribution_url'] : '', ['https']);
    $clean['provider_privacy_url'] = esc_url_raw(is_scalar($input['provider_privacy_url'] ?? null) ? (string) $input['provider_privacy_url'] : '', ['https']) ?: $default['provider_privacy_url'];
    $clean['search_provider_name'] = $clean['search_provider_name'] ?: $default['search_provider_name'];
    $clean['search_url'] = sanitize_search_url(is_scalar($input['search_url'] ?? null) ? (string) $input['search_url'] : '') ?: ($previous['search_url'] ?? $default['search_url']);
    $clean['search_info_url'] = esc_url_raw(is_scalar($input['search_info_url'] ?? null) ? (string) $input['search_info_url'] : '', ['https']) ?: $default['search_info_url'];
    $clean['schematic_style'] = organization_choice($input['schematic_style'] ?? null, ['dots' => 'dots', 'plain' => 'plain'], 'dots');
    $clean['map_palette'] = organization_choice($input['map_palette'] ?? null, ['custom' => 'custom', 'navy' => 'navy', 'forest' => 'forest', 'warm' => 'warm'], 'custom');
    if ($clean['map_palette'] !== 'custom') {
        $palette = color_presets()[$clean['map_palette']]['colors'];
        $clean['canvas_background'] = $palette['canvas_background'];
        $clean['river_color'] = $palette['river_color'];
    }
    return $clean;
}

function register_content(): void
{
    $label = settings()['menu_label'];
    register_post_type(TYPE, [
        'labels' => [
            'name' => $label,
            'singular_name' => 'Jednostka',
            'menu_name' => $label,
            'add_new' => 'Dodaj jednostkę',
            'add_new_item' => 'Dodaj jednostkę',
            'new_item' => 'Nowa jednostka',
            'edit_item' => 'Edytuj jednostkę',
            'view_item' => 'Podgląd jednostki',
            'search_items' => 'Szukaj jednostek',
            'not_found' => 'Nie znaleziono jednostek.',
            'not_found_in_trash' => 'Nie znaleziono jednostek w koszu.',
            'all_items' => 'Wszystkie jednostki',
            'featured_image' => 'Zdjęcie lub znak jednostki',
            'set_featured_image' => 'Ustaw zdjęcie lub znak',
            'remove_featured_image' => 'Usuń zdjęcie lub znak',
            'use_featured_image' => 'Użyj tego obrazu',
            'insert_into_item' => 'Wstaw do opisu jednostki',
            'uploaded_to_this_item' => 'Wgrane do tej jednostki',
            'item_published' => 'Jednostka opublikowana.',
            'item_updated' => 'Jednostka zaktualizowana.',
            'item_reverted_to_draft' => 'Jednostka przeniesiona do szkiców.',
            'items_list' => 'Lista jednostek',
            'filter_items_list' => 'Filtruj listę jednostek',
            'attributes' => 'Kolejność w wykazie',
        ],
        'public' => false,'publicly_queryable' => false,'exclude_from_search' => true,'show_ui' => true,
        'show_in_rest' => false,'show_in_nav_menus' => false,'show_in_admin_bar' => false,'has_archive' => false,
        'rewrite' => false,'query_var' => false,'menu_icon' => 'dashicons-networking',
        'supports' => ['title','editor','thumbnail','revisions','page-attributes'],
    ]);
    if (!current_theme_supports('post-thumbnails')) {
        add_theme_support('post-thumbnails', [TYPE]);
    }
    foreach ([PLACE => 'Obszary i miejscowości',KIND => 'Kolory oznaczeń'] as $taxonomy => $name) {
        register_taxonomy($taxonomy, TYPE, [
            'labels' => ['name' => $name,'singular_name' => $taxonomy === PLACE ? 'Obszar' : 'Metodyka / rodzaj','add_new_item' => 'Dodaj nową pozycję','edit_item' => 'Edytuj pozycję'],
            'public' => false,'publicly_queryable' => false,'show_ui' => true,'show_in_rest' => false,
            'show_in_nav_menus' => false,'rewrite' => false,'query_var' => false,'meta_box_cb' => false,
            'show_admin_column' => true,'show_in_quick_edit' => false,
        ]);
    }
}

function coordinates(string $url): ?array
{
    $parts = wp_parse_url($url);
    if (!$parts || !in_array(strtolower($parts['host'] ?? ''), ['openstreetmap.org','www.openstreetmap.org'], true)) {
        return null;
    }
    parse_str($parts['query'] ?? '', $query);
    if (isset($query['mlat'],$query['mlon']) && is_numeric($query['mlat']) && is_numeric($query['mlon'])) {
        $lat = (float)$query['mlat'];
        $lon = (float)$query['mlon'];
    } elseif (preg_match('~^map=\d+/(-?[\d.]+)/(-?[\d.]+)~', $parts['fragment'] ?? '', $match)) {
        $lat = (float)$match[1];
        $lon = (float)$match[2];
    } else {
        return null;
    }
    return is_finite($lat) && is_finite($lon) && abs($lat) <= 85 && abs($lon) <= 180 ? [$lat,$lon] : null;
}

function unit_fields(): array
{
    return ['number' => 'Numer / identyfikator jednostki','area' => 'Obszar działania, opis','age' => 'Wiek uczestników','leader' => 'Imię, nazwisko i stopień osoby prowadzącej','email' => 'E-mail do osoby prowadzącej','phone' => 'Telefon do kontaktu','facebook' => 'Facebook','instagram' => 'Instagram','website' => 'Strona internetowa','location_label' => 'Nazwa miejsca działania','specialty_name' => 'Nazwa innej zatwierdzonej specjalności'];
}

function sanitize_unit(array $input, array $previous = []): array
{
    $clean = [];
    foreach (unit_fields() as $key => $label) {
        $value = is_scalar($input[$key] ?? null) ? (string)$input[$key] : '';
        if ($key === 'email') {
            $value = sanitize_email($value);
        } elseif (in_array($key, ['facebook','instagram','website'], true)) {
            $value = esc_url_raw($value, ['http','https']);
        } else {
            $value = sanitize_text_field($value);
        }
        $clean[$key] = mb_substr($value, 0, $key === 'number' ? 40 : 1000);
    }
    return array_replace($clean, sanitize_organization($input, $previous));
}

function label_sides(): array
{
    return ['bottom' => 'Pod punktem','top' => 'Nad punktem','top-far' => 'Wyżej nad punktem','left' => 'Po lewej','right' => 'Po prawej'];
}

function sanitize_place(array $input): array
{
    $url = esc_url_raw(is_scalar($input['url'] ?? null) ? (string)$input['url'] : '', ['http','https']);
    $side = is_string($input['side'] ?? null) ? $input['side'] : '';
    $mobile = is_string($input['mobile_side'] ?? null) ? $input['mobile_side'] : '';
    $point = point_coordinates($input['latitude'] ?? '', $input['longitude'] ?? '');
    if (!$point && ($input['latitude'] ?? '') === '' && ($input['longitude'] ?? '') === '') {
        $point = coordinates($url);
    }
    return [
        'url' => coordinates($url) ? $url : '',
        'latitude' => $point ? (string) $point[0] : '',
        'longitude' => $point ? (string) $point[1] : '',
        'side' => isset(label_sides()[$side]) ? $side : 'bottom',
        'mobile_side' => isset(label_sides()[$mobile]) ? $mobile : 'bottom',
        'regional' => empty($input['regional']) ? 0 : 1,
    ];
}

function sanitize_lines($input): array
{
    if (!is_array($input)) {
        return [];
    }
    if (($input['type'] ?? '') === 'FeatureCollection') {
        $lines = [];
        if (!is_array($input['features'] ?? null)) {
            return [];
        }
        foreach (array_slice($input['features'], 0, 100) as $feature) {
            $lines = array_merge($lines, sanitize_lines($feature['geometry'] ?? []));
            if (array_sum(array_map('count', $lines)) > 20000) {
                return [];
            }
        }
        return $lines;
    }
    if (($input['type'] ?? '') === 'Feature') {
        return sanitize_lines($input['geometry'] ?? []);
    }
    if (isset($input['type'])) {
        $input = ($input['type'] === 'LineString') ? [$input['coordinates'] ?? []] : (($input['type'] === 'MultiLineString') ? ($input['coordinates'] ?? []) : []);
    }
    $clean = [];
    $total = 0;
    foreach (array_slice($input, 0, 100) as $line) {
        if (!is_array($line)) {
            continue;
        }
        $points = [];
        foreach ($line as $point) {
            if (++$total > 20000) {
                return [];
            }
            if (!is_array($point) || !isset($point[0],$point[1]) || !is_numeric($point[0]) || !is_numeric($point[1])) {
                return [];
            }
            $lon = (float)$point[0];
            $lat = (float)$point[1];
            if (!is_finite($lon) || !is_finite($lat) || abs($lon) > 180 || abs($lat) > 85) {
                return [];
            }
            $points[] = [$lon,$lat];
        }
        if (count($points) > 1) {
            $clean[] = $points;
        }
    }
    return $clean;
}

function luminance(string $hex): float
{
    if (strlen($hex) === 4) {
        $hex = '#'.$hex[1].$hex[1].$hex[2].$hex[2].$hex[3].$hex[3];
    }
    $rgb = array_map(static function ($value) {
        $value = hexdec($value) / 255;
        return $value <= .04045 ? $value / 12.92 : (($value + .055) / 1.055) ** 2.4;
    }, str_split(ltrim($hex, '#'), 2));
    return .2126 * $rgb[0] + .7152 * $rgb[1] + .0722 * $rgb[2];
}

function button_ink(string $hex): string
{
    $luminance = luminance($hex);
    return (1.05 / ($luminance + .05)) >= (($luminance + .05) / .05) ? '#ffffff' : '#000000';
}

function contrast_ink(string $background, string $preferred): string
{
    $back = luminance($background);
    $front = luminance($preferred);
    $contrast = (max($back, $front) + .05) / (min($back, $front) + .05);
    return $contrast >= 4.5 ? $preferred : button_ink($background);
}
