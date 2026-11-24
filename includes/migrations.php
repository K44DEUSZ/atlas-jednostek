<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function legacy_organization(array $fields, string $slug): array
{
    $map = [
        'zuchy' => ['pack', ['cub']], 'harcerze' => ['troop', ['scout']],
        'starsi' => ['troop', ['older_scout']], 'wedrownicy' => ['troop', ['rover']],
        'wielopoziomowe' => ['troop', []],
    ];
    [$type, $methods] = $map[$slug] ?? ['', []];
    $fields['unit_type'] = $type;
    $fields['methodologies'] = $methods;
    $fields['legacy_multilevel'] = $slug === 'wielopoziomowe';
    $fields['leader_gender'] = 'neutral';
    if (preg_match('/^(Drużynowa|Drużynowy)\s*:\s*(.*)$/u', $fields['leader'] ?? '', $match)) {
        $fields['leader_gender'] = $match[1] === 'Drużynowa' ? 'female' : 'male';
        $fields['leader'] = $match[2];
    }
    return array_replace($fields, sanitize_organization($fields, $fields));
}

function unit_data(int $id): array
{
    $fields = (array) get_post_meta($id, META, true);
    if (!array_key_exists('unit_type', $fields)) {
        $terms = wp_get_object_terms($id, KIND);
        $slug = !is_wp_error($terms) && $terms ? $terms[0]->slug : '';
        $fields = legacy_organization($fields, $slug);
    }
    return $fields;
}

function migrate_schema(): void
{
    if ((int) get_option(SCHEMA_OPTION, 0) >= SCHEMA_VERSION) {
        return;
    }
    foreach (classification_defaults() as $slug => $unused) {
        classification_term($slug);
    }
    $offset = 0;
    do {
        $ids = get_posts(['post_type' => TYPE, 'post_status' => array_values(get_post_stati()), 'numberposts' => 100, 'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids']);
        foreach ($ids as $id) {
            $old = (array) get_post_meta($id, META, true);
            if (array_key_exists('unit_type', $old)) {
                continue;
            }
            add_post_meta($id, '_botud_legacy_organization', $old, true);
            update_post_meta($id, META, unit_data($id));
        }
        $offset += count($ids);
    } while (count($ids) === 100);
    foreach (get_terms(['taxonomy' => PLACE, 'hide_empty' => false]) as $term) {
        $meta = (array) get_term_meta($term->term_id, TERM_META, true);
        $point = place_coordinates($meta);
        if ($point) {
            $meta['latitude'] = (string) $point[0];
            $meta['longitude'] = (string) $point[1];
            update_term_meta($term->term_id, TERM_META, $meta);
        }
    }
    $settings = settings();
    if (in_array($settings['menu_label'], ['Jednostki', 'Jednostki hufca', ''], true)) {
        $settings['menu_label'] = 'Atlas Jednostek';
    }
    update_option(OPTION, $settings, false);
    update_option(SCHEMA_OPTION, SCHEMA_VERSION, false);
}

add_action('admin_init', __NAMESPACE__.'\\migrate_schema');
