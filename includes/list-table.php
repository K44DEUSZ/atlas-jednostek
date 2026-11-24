<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

add_filter('views_edit-'.TYPE, static function (array $views): array {
    unset($views['mine']);
    return $views;
});

add_action('load-edit.php', static function (): void {
    $screen = get_current_screen();
    $type = get_post_type_object(TYPE);
    if ($screen->post_type !== TYPE || !current_user_can($type->cap->edit_others_posts)) {
        return;
    }
    if (isset($_GET['author']) || isset($_GET['author_name'])) {
        wp_safe_redirect(remove_query_arg(['author', 'author_name', 'paged']));
        exit;
    }
});

add_filter('manage_'.TYPE.'_posts_columns', static function (array $columns): array {
    $date = $columns['date'] ?? 'Data';
    unset($columns['date'], $columns['taxonomy-'.KIND]);
    $columns['botud_type'] = 'Typ jednostki';
    $columns['botud_number'] = 'Numer';
    $columns['botud_contact'] = 'Osoba prowadząca i kontakt';
    $columns['date'] = $date;
    return $columns;
});

add_filter('default_hidden_columns', static function (array $hidden, \WP_Screen $screen): array {
    if ($screen->base === 'edit' && $screen->post_type === TYPE) {
        $hidden = array_merge($hidden, ['botud_number', 'botud_contact']);
    }
    return $hidden;
}, 10, 2);

add_action('manage_'.TYPE.'_posts_custom_column', static function (string $column, int $id): void {
    $fields = unit_data($id);
    if ($column === 'botud_type') {
        echo esc_html(unit_type_label($fields));
    } elseif ($column === 'botud_number') {
        echo esc_html($fields['number'] ?? '');
    } elseif ($column === 'botud_contact') {
        echo esc_html($fields['leader'] ?? '');
        $email = sanitize_email($fields['email'] ?? '');
        if ($email) {
            echo '<br><a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a>';
        }
    }
}, 10, 2);

add_action('restrict_manage_posts', static function (string $post_type): void {
    if ($post_type !== TYPE) {
        return;
    }
    $selected = organization_choice($_GET['botud_unit_type'] ?? null, unit_types());
    echo '<label class="screen-reader-text" for="botud-type-filter">Typ jednostki</label><select id="botud-type-filter" name="botud_unit_type"><option value="">Wszystkie typy jednostek</option>';
    foreach (unit_types() as $key => $label) {
        echo '<option value="'.$key.'" '.selected($selected, $key, false).'>'.$label.'</option>';
    }
    echo '</select>';
});

add_action('pre_get_posts', static function (\WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== TYPE) {
        return;
    }
    $type = organization_choice($_GET['botud_unit_type'] ?? null, unit_types());
    if ($type !== '') {
        $filters = (array) $query->get('meta_query');
        $filters[] = ['key' => META, 'value' => '"unit_type";s:'.strlen($type).':"'.$type.'"', 'compare' => 'LIKE'];
        $query->set('meta_query', $filters);
    }
});
