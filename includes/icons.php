<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function method_icon_choices(): array
{
    return [
        'zuchy' => 'Metodyka zuchowa',
        'harcerze' => 'Metodyka harcerska',
        'starsi' => 'Metodyka starszoharcerska',
        'wedrownicy' => 'Metodyka wędrownicza',
        'wielopoziomowe' => 'Drużyny wielopoziomowe',
    ];
}

function sanitize_method_icons(array $raw): array
{
    $clean = [];
    foreach (method_icon_choices() as $slug => $unused) {
        $value = $raw[$slug] ?? null;
        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            continue;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            continue;
        }
        $attachment = get_post($id);
        if (!$attachment || $attachment->post_type !== 'attachment' || $attachment->post_status === 'trash' || !preg_match('/^image\/[a-z0-9.+-]+$/i', (string) $attachment->post_mime_type)) {
            continue;
        }
        $clean[$slug] = $id;
    }
    return $clean;
}

function method_icon_attachment_url(int $id): string
{
    $url = wp_get_attachment_image_url($id, 'thumbnail') ?: wp_get_attachment_url($id);
    return $url ? esc_url($url, ['http', 'https']) : '';
}

function method_icon_content(string $slug, array $icons): string
{
    if (!empty($icons[$slug])) {
        $url = method_icon_attachment_url($icons[$slug]);
        if ($url !== '') {
            return '<img src="'.$url.'" alt="" width="32" height="32" decoding="async">';
        }
    }
    if ($slug === 'wielopoziomowe') {
        $html = '';
        foreach (['zuchy', 'harcerze', 'starsi', 'wedrownicy'] as $part) {
            $html .= '<span class="botud-method-icon-part">'.method_icon_content($part, $icons).'</span>';
        }
        return $html;
    }
    $shape = match ($slug) {
        'zuchy' => '<circle cx="16" cy="16" r="6"/><path d="M16 2v4m0 20v4M2 16h4m20 0h4M6 6l3 3m14 14 3 3M6 26l3-3M23 9l3-3"/>',
        'harcerze' => '<circle cx="16" cy="16" r="12"/><path d="m21 11-3 8-7 2 3-8 7-2Z"/><circle cx="16" cy="16" r="1" fill="currentColor" stroke="none"/>',
        'starsi' => '<path d="M2 27 13 6l8 14 4-8 6 15H2Zm8-15 3 5 3-5M20 21l3 6"/>',
        'wedrownicy' => '<path d="M17 2c2 7-6 9-5 16 2-1 4-4 4-7 5 4 9 8 9 12a9 9 0 0 1-18 0c0-7 6-10 10-21Z"/><path d="M16 21c-3 3-3 6 0 8 3-2 3-5 0-8Z"/>',
        default => '',
    };
    return $shape === '' ? '' : '<svg viewBox="0 0 32 32" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">'.$shape.'</svg>';
}

function method_icon_markup(string $slug, array $icons): string
{
    if (!isset(method_icon_choices()[$slug])) {
        return '';
    }
    $combined = $slug === 'wielopoziomowe' && (empty($icons[$slug]) || method_icon_attachment_url($icons[$slug]) === '');
    $class = 'botud-method-icon'.($combined ? ' botud-method-icon--combined' : '');
    return '<span class="'.$class.'" aria-hidden="true">'.method_icon_content($slug, $icons).'</span>';
}

function methodology_icon(string $slug): string
{
    $settings = settings();
    $icons = sanitize_method_icons(is_array($settings['method_icons'] ?? null) ? $settings['method_icons'] : []);
    return method_icon_markup($slug, $icons);
}

function method_icon_settings(array $settings): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $icons = sanitize_method_icons(is_array($settings['method_icons'] ?? null) ? $settings['method_icons'] : []);
    echo '<h2>Ikony metodyk</h2><p>Możesz wybrać własne obrazy z biblioteki mediów. Domyślne symbole są uproszczonymi ikonami pomocniczymi, nie oficjalnymi znakami ZHP. Bez osobnej ikony drużyny wielopoziomowe pokazują cztery symbole metodyk.</p><table class="form-table">';
    foreach (method_icon_choices() as $slug => $label) {
        $id = 'botud-method-icon-'.$slug;
        $attachment_id = $icons[$slug] ?? 0;
        echo '<tr data-botud-icon-field data-icon-slug="'.esc_attr($slug).'"><th scope="row" id="'.$id.'-label">'.esc_html($label).'</th><td>';
        echo '<div data-icon-preview aria-hidden="true">'.method_icon_markup($slug, $icons).'</div>';
        echo '<template data-icon-fallback>'.method_icon_markup($slug, []).'</template>';
        echo '<input type="hidden" id="'.$id.'" name="botud_settings[method_icons]['.esc_attr($slug).']" value="'.esc_attr((string) $attachment_id).'" data-icon-id>';
        echo '<p><button type="button" class="button" id="'.$id.'-choose" data-icon-choose aria-label="'.esc_attr('Wybierz ikonę: '.$label).'" aria-describedby="'.$id.'-label">Wybierz ikonę</button> <button type="button" class="button" id="'.$id.'-remove" data-icon-remove aria-label="'.esc_attr('Usuń własną ikonę: '.$label).'"'.($attachment_id ? '' : ' disabled').'>Usuń</button></p>';
        echo '<p class="description" role="status" aria-live="polite" data-icon-status></p></td></tr>';
    }
    echo '</table>';
}

add_action('admin_enqueue_scripts', static function (): void {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== TYPE.'_page_botud-settings' || settings_tab() !== 'appearance' || !current_user_can('manage_options')) {
        return;
    }
    wp_add_inline_style('common', <<<'CSS'
[data-botud-icon-field] .botud-method-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; line-height: 0; }
[data-botud-icon-field] .botud-method-icon :is(svg, img) { display: block; width: 32px; height: 32px; object-fit: contain; }
[data-botud-icon-field] .botud-method-icon--combined { display: inline-grid; grid-template-columns: repeat(2, 15px); grid-template-rows: repeat(2, 15px); gap: 2px; }
[data-botud-icon-field] .botud-method-icon-part { display: flex; align-items: center; justify-content: center; width: 15px; height: 15px; }
[data-botud-icon-field] .botud-method-icon-part :is(svg, img) { width: 15px; height: 15px; }
CSS);
    wp_enqueue_media();
    wp_enqueue_script('atlas-jednostek-method-icons', plugins_url('assets/method-icons-admin.js', dirname(__DIR__).'/atlas-jednostek.php'), ['media-views'], asset_version('assets/method-icons-admin.js'), true);
});
