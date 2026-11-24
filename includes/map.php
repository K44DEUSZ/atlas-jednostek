<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function map_bounds(array $points, array $configured): array
{
    $latitudes = array_column(array_column($points, 'point'), 0);
    $longitudes = array_column(array_column($points, 'point'), 1);
    if (isset($configured['south'], $configured['north'], $configured['west'], $configured['east'])) {
        $latitudes = array_merge($latitudes, [$configured['south'], $configured['north']]);
        $longitudes = array_merge($longitudes, [$configured['west'], $configured['east']]);
    }
    $bounds = ['south' => min($latitudes), 'north' => max($latitudes), 'west' => min($longitudes), 'east' => max($longitudes)];
    if ($bounds['north'] - $bounds['south'] < .001) {
        $bounds['south'] -= .01;
        $bounds['north'] += .01;
    }
    if ($bounds['east'] - $bounds['west'] < .001) {
        $bounds['west'] -= .01;
        $bounds['east'] += .01;
    }
    return $bounds;
}

function map_markup(array $groups, array $settings, string $id): string
{
    $points = array_filter($groups, static fn ($group) => !empty($group['point']));
    if (!$points || $settings['layout'] === 'list') {
        return '';
    }

    $bounds = map_bounds($points, (array) $settings['bounds']);
    $project = static fn ($lat, $lon) => [
        10 + ($lon - $bounds['west']) / ($bounds['east'] - $bounds['west']) * 80,
        12 + ($bounds['north'] - $lat) / ($bounds['north'] - $bounds['south']) * 76,
    ];
    $lines = sanitize_lines($settings['lines']);
    $map_mode = $settings['map_mode'] ?? (!empty($settings['road_map']) ? 'switchable' : 'schematic');
    if (!in_array($map_mode, ['schematic', 'road', 'switchable'], true)) {
        $map_mode = 'schematic';
    }
    $plugin_file = dirname(__DIR__).'/atlas-jednostek.php';
    $config = [
        'bounds' => $bounds,
        'lines' => $lines,
        'lineColor' => sanitize_hex_color($settings['river_color']) ?: defaults()['river_color'],
        'maxZoom' => max(12, min(19, (int) $settings['max_zoom'])),
        'map_mode' => $map_mode,
        'allowRoad' => $map_mode !== 'schematic',
        'tilesUrl' => sanitize_tile_url((string) $settings['tiles_url']) ?: defaults()['tiles_url'],
        'providerName' => $settings['provider_name'],
        'providerAttribution' => $settings['provider_attribution'],
        'providerAttributionUrl' => $settings['provider_attribution_url'],
        'scriptUrl' => plugins_url('assets/vendor/leaflet/leaflet.js', $plugin_file),
        'styleUrl' => plugins_url('assets/vendor/leaflet/leaflet.css', $plugin_file),
    ];
    $html = '<section class="botud-map-panel" aria-labelledby="'.$id.'map-title">';
    $html .= '<h2 class="botud-heading" id="'.$id.'map-title">'.esc_html($settings['map_title']).'</h2>';
    $html .= '<p>Wybierz punkt, aby wyświetlić jednostki z danego obszaru.</p>';
    $html .= '<div class="botud-map-controls" hidden>';
    if ($map_mode === 'switchable') {
        $html .= '<div class="botud-map-modes" role="group" aria-label="Rodzaj mapy">';
        $html .= '<button type="button" class="botud-control" data-map-mode="schematic" aria-pressed="true">Schemat</button>';
        $html .= '<button type="button" class="botud-control" data-map-mode="road" aria-pressed="false">Mapa drogowa</button></div>';
    }
    $html .= '<div class="botud-map-navigation" role="group" aria-label="Przybliżanie mapy">';
    $html .= '<button type="button" class="botud-control botud-square" data-map-action="out" aria-label="Oddal mapę">-</button>';
    $html .= '<button type="button" class="botud-control" data-map-action="fit">Pokaż wszystkie punkty</button>';
    $html .= '<button type="button" class="botud-control botud-square" data-map-action="in" aria-label="Przybliż mapę">+</button></div></div>';
    $html .= '<div class="botud-map" role="region" aria-label="Mapa obszarów działania. Przesuwaj strzałkami, przybliżaj plusem i oddalaj minusem." data-map-mode="'.$map_mode.'" data-map-config="'.esc_attr(wp_json_encode($config)).'">';
    $html .= '<span class="botud-north" aria-hidden="true">↑ N</span>';
    if ($settings['map_label']) {
        $html .= '<span class="botud-area" aria-hidden="true">'.esc_html($settings['map_label']).'</span>';
    }
    if ($map_mode === 'road') {
        $html .= '<div class="botud-map-activation" hidden><p>Mapa drogowa zostanie pobrana od dostawcy: '.esc_html($settings['provider_name']).'. <a href="'.esc_url($settings['provider_privacy_url'], ['https']).'">Prywatność dostawcy</a>.</p><button type="button" class="botud-control" data-map-activate>Włącz mapę drogową</button></div>';
    }
    if ($lines) {
        $html .= '<svg class="botud-geography" viewBox="0 0 1000 1000" preserveAspectRatio="none" aria-hidden="true">';
        foreach ($lines as $line) {
            $path = [];
            foreach ($line as $point) {
                [$x, $y] = $project($point[1], $point[0]);
                $path[] = round($x * 10, 1).','.round($y * 10, 1);
            }
            $html .= '<path d="M'.implode(' L', $path).'" fill="none" stroke="currentColor" stroke-width="18" stroke-linecap="round" stroke-linejoin="round"/>';
        }
        $html .= '</svg>';
    }
    foreach ($points as $slug => $group) {
        [$x, $y] = $project(...$group['point']);
        $meta = $group['meta'];
        $side = isset(label_sides()[$meta['side'] ?? '']) ? $meta['side'] : 'bottom';
        $mobile = isset(label_sides()[$meta['mobile_side'] ?? '']) ? $meta['mobile_side'] : $side;
        $filter = isset($group['place_filter']) ? 'data-place-filter="'.esc_attr($group['place_filter']).'"' : 'data-point-filter="'.esc_attr($slug).'" data-unit-ids="'.esc_attr(implode(' ', $group['unit_ids'])).'"';
        $html .= '<button type="button" class="botud-pin botud-side-'.$side.' botud-mobile-'.$mobile.'" '.$filter.' data-lat="'.esc_attr((string) $group['point'][0]).'" data-lon="'.esc_attr((string) $group['point'][1]).'" title="'.esc_attr($group['name']).'" style="--botud-x:'.round($x, 2).'%;--botud-y:'.round($y, 2).'%" aria-pressed="false" aria-label="'.esc_attr($group['name'].', liczba jednostek: '.count($group['units'])).'">';
        $html .= '<span class="botud-dot" aria-hidden="true"></span><span class="botud-pin-label"><span>'.esc_html($group['name']).'</span><small>'.count($group['units']).'</small></span></button>';
    }
    $html .= '</div><div class="botud-map-tools" hidden><details class="botud-pan"><summary>Sterowanie przyciskami</summary><div role="group" aria-label="Przesuwanie mapy">';
    foreach (['north' => ['↑', 'Przesuń mapę na północ'], 'west' => ['←', 'Przesuń mapę na zachód'], 'east' => ['→', 'Przesuń mapę na wschód'], 'south' => ['↓', 'Przesuń mapę na południe']] as $direction => $control) {
        $html .= '<button type="button" class="botud-control botud-square" data-map-action="'.$direction.'" aria-label="'.$control[1].'">'.$control[0].'</button>';
    }
    $html .= '</div></details><output class="botud-zoom-status" aria-live="polite"></output></div>';
    if ($map_mode === 'switchable') {
        $html .= '<p class="botud-provider-note">Po włączeniu mapy drogowej podkład zostanie pobrany od dostawcy: '.esc_html($settings['provider_name']).'. <a href="'.esc_url($settings['provider_privacy_url'], ['https']).'">Prywatność dostawcy</a>.</p>';
    }
    $html .= '<p class="botud-map-error" role="status" hidden></p>';
    $html .= '<p class="botud-selection" aria-live="polite">Wszystkie obszary</p>';
    $html .= '<a class="botud-show-results botud-link" href="#'.$id.'results" hidden>Zobacz wybrane jednostki</a>';
    $html .= '<p class="botud-map-note">'.esc_html($settings['map_note']).'</p>';
    $html .= '<noscript><p>Wszystkie jednostki znajdziesz w wykazie poniżej. Interaktywna mapa wymaga JavaScriptu.</p></noscript></section>';
    return $html;
}
