<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function directory_data(): array
{
    $posts = get_posts(['post_type' => TYPE, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC']]);
    $groups = [];
    $kinds = [];
    $types = filter_unit_types();
    $type_counts = array_fill_keys(array_keys($types), 0);
    $facets = context_facets();
    foreach ($facets as &$facet) {
        $facet['counts'] = array_fill_keys(array_keys($facet['choices']), 0);
    }
    unset($facet);
    $units = [];
    foreach ($posts as $post) {
        $fields = unit_data($post->ID);
        $terms = wp_get_object_terms($post->ID, KIND);
        $kind = get_term_by('slug', unit_classification($fields), KIND) ?: (!is_wp_error($terms) && $terms ? $terms[0] : null);
        $unit = ['post' => $post, 'kind' => $kind, 'fields' => $fields, 'filter_keys' => unit_filter_keys($fields)];
        $units[$post->ID] = $unit;
        $filter_type = unit_filter_type($fields);
        if (isset($type_counts[$filter_type])) {
            $type_counts[$filter_type]++;
        }
        foreach ($facets as $key => &$facet) {
            $value = $fields[$key] ?? '';
            if ($filter_type === $facet['type'] && isset($facet['counts'][$value])) {
                $facet['counts'][$value]++;
            }
        }
        unset($facet);
        foreach ($filter_type === 'troop' ? $unit['filter_keys'] : [] as $key) {
            $term = get_term_by('slug', $key, KIND);
            if ($term) {
                $kinds[$key]['term'] = $term;
                $kinds[$key]['label'] = ['zuchy' => 'Zuchowa', 'harcerze' => 'Harcerska', 'starsi' => 'Starszoharcerska', 'wedrownicy' => 'Wędrownicza', 'wielopoziomowe' => 'Wielopoziomowa'][$key];
                $kinds[$key]['ids'][$post->ID] = true;
            }
        }
        $places = wp_get_object_terms($post->ID, PLACE);
        if (!$places || is_wp_error($places)) {
            $groups['unassigned']['name'] = 'Pozostałe jednostki';
            $groups['unassigned']['units'][] = $unit;
            continue;
        }
        foreach ($places as $place) {
            $meta = (array) get_term_meta($place->term_id, TERM_META, true);
            $groups[$place->slug]['name'] = $place->name;
            $groups[$place->slug]['meta'] = $meta;
            $groups[$place->slug]['point'] = place_coordinates($meta);
            $groups[$place->slug]['units'][] = $unit;
        }
    }
    uasort($groups, static fn ($a, $b) => strcasecmp(remove_accents($a['name']), remove_accents($b['name'])));
    uasort($kinds, static function ($a, $b) {
        $order = (int) get_term_meta($a['term']->term_id, '_botud_order', true) <=> (int) get_term_meta($b['term']->term_id, '_botud_order', true);
        return $order ?: strcasecmp(remove_accents($a['term']->name), remove_accents($b['term']->name));
    });
    $points = [];
    foreach ($groups as $slug => $group) {
        $uses_area_point = array_filter($group['units'], static fn ($unit) => !point_coordinates($unit['fields']['latitude'] ?? '', $unit['fields']['longitude'] ?? ''));
        if (!empty($group['point']) && $uses_area_point) {
            $points[$slug] = $group;
            $points[$slug]['place_filter'] = $slug;
        }
    }
    foreach ($units as $unit) {
        $point = point_coordinates($unit['fields']['latitude'] ?? '', $unit['fields']['longitude'] ?? '');
        if (!$point) {
            continue;
        }
        $key = 'point-'.substr(hash('sha256', wp_json_encode($point)), 0, 12);
        $label = $unit['fields']['location_label'] ?: $unit['post']->post_title;
        if (!isset($points[$key])) {
            $points[$key] = ['name' => $label, 'point' => $point, 'meta' => [], 'units' => [], 'unit_ids' => []];
        } elseif ($points[$key]['name'] !== $label) {
            $points[$key]['name'] = 'Wspólne miejsce działania';
        }
        $points[$key]['units'][] = $unit;
        $points[$key]['unit_ids'][] = $unit['post']->ID;
    }
    $types = array_intersect_key($types, array_filter($type_counts));
    return ['groups' => $groups, 'kinds' => $kinds, 'types' => $types, 'type_counts' => $type_counts, 'facets' => $facets, 'points' => $points, 'total' => count($posts)];
}

function card(array $unit): string
{
    $post = $unit['post'];
    $fields = $unit['fields'];
    $kind = $unit['kind'];
    $html = '<article class="botud-unit" data-unit-id="'.$post->ID.'" data-kind="'.esc_attr($kind ? $kind->slug : '').'" data-methods="'.esc_attr(implode(' ', unit_filter_keys($fields))).'" data-unit-type="'.esc_attr(unit_filter_type($fields)).'" data-circle-type="'.esc_attr($fields['circle_type'] ?? '').'" data-club-type="'.esc_attr($fields['club_type'] ?? '').'" data-specialty="'.esc_attr($fields['specialty'] ?? '').'">';
    $image = get_the_post_thumbnail($post, 'medium', ['class' => 'botud-unit-image', 'loading' => 'lazy']);
    if ($image) {
        $html .= $image;
    }
    if ($kind) {
        $color = sanitize_hex_color(get_term_meta($kind->term_id, '_botud_color', true)) ?: '#687189';
        $html .= '<p class="botud-tag" style="--botud-kind:'.esc_attr($color).'">'.esc_html($fields['unit_type'] ? unit_type_label($fields) : $kind->name).'</p>';
    }
    $html .= '<h3>'.esc_html($post->post_title).'</h3>';
    $number = (string) ($fields['number'] ?? '');
    if ($number !== '' && !preg_match('/^'.preg_quote($number, '/').'(?:[\s.]|$)/u', $post->post_title)) {
        $html .= '<p>Numer jednostki: '.esc_html($number).'</p>';
    }
    if ($post->post_content) {
        $html .= '<div class="botud-description">'.wpautop(wp_kses_post($post->post_content)).'</div>';
    }
    foreach (['area','age'] as $key) {
        if (!empty($fields[$key])) {
            $html .= '<p>'.esc_html($fields[$key]).'</p>';
        }
    }
    if (!empty($fields['leader'])) {
        $html .= '<p>'.esc_html(leader_title($fields).': '.$fields['leader']).'</p>';
    }
    if (count($fields['methodologies'] ?? []) > 1) {
        $names = array_map(static fn ($key) => methodologies()[$key], $fields['methodologies']);
        $html .= '<p>Metodyki: '.esc_html(implode(', ', $names)).'</p>';
    }
    if (!empty($fields['specialty'])) {
        $specialty = $fields['specialty'] === 'other' ? ($fields['specialty_name'] ?? '') : (specialties()[$fields['specialty']] ?? '');
        $html .= $specialty ? '<p>Specjalność: '.esc_html($specialty).'</p>' : '';
    }
    $links = '';
    $email = sanitize_email($fields['email'] ?? '');
    if ($email) {
        $label = 'Napisz do '.leader_title($fields, true);
        $links .= '<a class="botud-link botud-contact" href="mailto:'.esc_attr($email).'" aria-label="'.esc_attr($label.': '.$post->post_title).'">'.esc_html($label).'</a>';
    }
    if (!empty($fields['phone'])) {
        $links .= '<a class="botud-link" href="tel:'.esc_attr(preg_replace('/[^+0-9]/', '', $fields['phone'])).'">'.esc_html($fields['phone']).'</a>';
    }
    foreach (['facebook' => 'Facebook','instagram' => 'Instagram','website' => 'Strona internetowa'] as $key => $label) {
        if (!empty($fields[$key])) {
            $links .= '<a class="botud-link" href="'.esc_url($fields[$key], ['http','https']).'">'.$label.'</a>';
        }
    }
    return $html.($links ? '<div class="botud-links">'.$links.'</div>' : '').'</article>';
}

function render_directory(): string
{
    $settings = settings();
    $data = directory_data();
    if (!$data['total']) {
        return '<div class="botud"><p>Nie ma jeszcze opublikowanych jednostek.</p></div>';
    }
    wp_enqueue_script('atlas-jednostek-filters');
    $id = wp_unique_id('botud-');
    $groups = $data['groups'];
    $kinds = $data['kinds'];
    $default = defaults();
    $accent = sanitize_hex_color($settings['accent']) ?: $default['accent'];
    $ink = button_ink($accent) === '#ffffff' ? $accent : '#182142';
    $variables = ['accent' => $accent, 'ink' => $ink, 'button-ink' => button_ink($accent)];
    foreach (['surface' => 'tint', 'canvas_background' => 'canvas', 'river_color' => 'river', 'card_background' => 'card'] as $key => $variable) {
        $variables[$variable] = sanitize_hex_color($settings[$key]) ?: $default[$key];
    }
    $variables['surface-ink'] = contrast_ink($variables['tint'], $ink);
    $variables['card-ink'] = contrast_ink($variables['card'], $ink);
    $variables['radius'] = max(0, min(24, (int) $settings['radius'])).'px';
    $variables['map-height'] = max(280, min(800, (int) $settings['map_height'])).'px';
    $variables['map-height-mobile'] = max(280, min(600, (int) $settings['map_height_mobile'])).'px';
    $style = '';
    foreach ($variables as $key => $value) {
        $style .= '--botud-'.$key.':'.$value.';';
    }
    $options = ['legacyHashes' => (array) $settings['legacy_hashes'], 'expandGroups' => (bool) $settings['expand_groups'], 'showKindFilters' => (bool) $settings['show_kind_filters']];
    $html = '<div class="botud" data-options="'.esc_attr(wp_json_encode($options)).'" data-layout="'.esc_attr($settings['layout']).'" data-density="'.esc_attr($settings['density']).'" data-schematic-style="'.esc_attr($settings['schematic_style']).'" data-show-labels="'.($settings['show_labels'] ? 'true' : 'false').'" data-show-counts="'.($settings['show_counts'] ? 'true' : 'false').'" style="'.esc_attr($style).'">';
    if ($settings['intro']) {
        $html .= '<p class="botud-intro">'.esc_html($settings['intro']).'</p>';
    }
    $html .= '<div class="botud-kinds" aria-label="Metodyki" hidden>';
    foreach ($kinds as $slug => $kind) {
        $term = $kind['term'];
        $color = sanitize_hex_color(get_term_meta($term->term_id, '_botud_color', true)) ?: '#687189';
        $html .= '<button type="button" class="botud-tag" style="--botud-kind:'.esc_attr($color).'" data-kind-filter="'.esc_attr($slug).'" aria-pressed="false">'.methodology_icon($slug).'<span class="botud-method-label">'.esc_html($kind['label']).'</span><span class="botud-method-count">'.count($kind['ids']).'</span><span class="botud-close" aria-hidden="true">×</span></button>';
    }
    $html .= '</div><div class="botud-filters" hidden><div class="botud-filter-field"><label for="'.$id.'place">Obszar działania</label><select id="'.$id.'place" data-place-select><option value="">Wszystkie obszary</option>';
    foreach ($groups as $slug => $group) {
        $html .= '<option value="'.esc_attr($slug).'" data-count="'.count($group['units']).'">'.esc_html($group['name']).'</option>';
    }
    $html .= '</select></div><div class="botud-filter-field"><label for="'.$id.'type">Typ jednostki</label><select id="'.$id.'type" data-type-select><option value="">Wszystkie typy</option>';
    foreach ($data['types'] as $key => $label) {
        $html .= '<option value="'.esc_attr($key).'" data-count="'.$data['type_counts'][$key].'">'.esc_html($label).'</option>';
    }
    $html .= '</select></div><div class="botud-filter-field" data-context-type="troop" hidden><label for="'.$id.'kind">Metodyka</label><select id="'.$id.'kind" data-kind-select><option value="">Wszystkie metodyki</option>';
    foreach ($kinds as $slug => $kind) {
        $html .= '<option value="'.esc_attr($slug).'" data-count="'.count($kind['ids']).'">'.esc_html($kind['label']).'</option>';
    }
    $html .= '</select></div>';
    foreach ($data['facets'] as $key => $facet) {
        $choices = array_intersect_key($facet['choices'], array_filter($facet['counts']));
        if (!$choices) {
            continue;
        }
        $field_id = $id.str_replace('_', '-', $key);
        $html .= '<div class="botud-filter-field" data-context-type="'.esc_attr($facet['type']).'" hidden><label for="'.$field_id.'">'.esc_html($facet['label']).'</label><select id="'.$field_id.'" data-facet-select data-unit-field="'.esc_attr($facet['field']).'"><option value="">'.esc_html($facet['empty']).'</option>';
        foreach ($choices as $value => $label) {
            $html .= '<option value="'.esc_attr($value).'" data-count="'.$facet['counts'][$value].'">'.esc_html($label).'</option>';
        }
        $html .= '</select></div>';
    }
    $html .= '<button type="button" class="botud-reset">Wyczyść filtry</button></div><div class="botud-layout">';
    $html .= map_markup($data['points'], $settings, $id);
    $html .= '<section class="botud-results" id="'.$id.'results" tabindex="-1" aria-labelledby="'.$id.'results-title"><h2 class="botud-heading" id="'.$id.'results-title">'.esc_html($settings['title']).'</h2><p class="botud-count" role="status" aria-live="polite">Liczba jednostek: '.$data['total'].'</p><p class="botud-empty" hidden>Brak jednostek dla wybranych filtrów. Zmień wybrane filtry.</p><div class="botud-localities">';
    foreach ($groups as $slug => $group) {
        $html .= '<details class="botud-locality" data-place="'.esc_attr($slug).'"'.($settings['expand_groups'] ? ' open' : '').'><summary><span>'.esc_html($group['name']).'</span><span class="botud-locality-count">'.count($group['units']).'</span></summary><div class="botud-units">';
        foreach ($group['units'] as $unit) {
            $html .= card($unit);
        }
        $html .= '</div></details>';
    }
    return $html.'</div></section></div></div>';
}
