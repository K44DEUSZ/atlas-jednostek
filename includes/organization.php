<?php

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;

function unit_types(): array
{
    return ['pack' => 'Gromada Zuchowa', 'troop' => 'Drużyna', 'club' => 'Klub', 'circle' => 'Krąg', 'group' => 'Szczep', 'troop_union' => 'Związek Drużyn', 'district' => 'Hufiec'];
}

function methodologies(): array
{
    return ['cub' => 'Zuchowa', 'scout' => 'Harcerska', 'older_scout' => 'Starszoharcerska', 'rover' => 'Wędrownicza'];
}

function circle_types(): array
{
    return ['academic' => 'Akademicki', 'seminary' => 'Klerycki', 'instructor' => 'Instruktorski', 'older_members' => 'Starszyzny', 'senior' => 'Seniorów', 'instructor_older' => 'Instruktorów i starszyzny', 'instructor_senior' => 'Instruktorów i seniorów', 'older_senior' => 'Starszyzny i seniorów', 'mixed' => 'Instruktorów, starszyzny i seniorów'];
}

function club_types(): array
{
    return ['specialty_club' => 'Harcerski Klub Specjalnościowy (HKS)', 'rescue_group' => 'Harcerska Grupa Ratownicza (HGR)'];
}

function specialties(): array
{
    return [
        'art' => 'Artystyczna', 'ecology' => 'Ekologiczna', 'horse' => 'Jeździecka i kawaleryjska',
        'border' => 'Harcerskiej Służby Granicznej', 'order' => 'Harcerskiej Służby Porządkowej', 'traffic' => 'Harcerskiej Służby Ruchu Drogowego',
        'aviation' => 'Lotnicza', 'communications' => 'Łącznościowa i informatyczna', 'defence' => 'Obronna', 'post' => 'Poczt Harcerskich',
        'rescue' => 'Ratownicza', 'fire' => 'Pożarnicza', 'sport' => 'Sportowa', 'tourism' => 'Turystyczno-krajoznawcza',
        'water' => 'Wodna i żeglarska', 'history' => 'Historyczno-rekonstrukcyjna', 'other' => 'Inna zatwierdzona specjalność',
    ];
}

function leader_roles(string $type): array
{
    if ($type === 'circle') {
        return ['commander' => 'Komendant / komendantka kręgu', 'chair' => 'Przewodniczący / przewodnicząca kręgu'];
    }
    if ($type === 'club') {
        return ['chair' => 'Przewodniczący / przewodnicząca klubu', 'head' => 'Szef / szefowa klubu lub grupy', 'president' => 'Prezes / prezeska', 'commodore' => 'Komandor / komandorka'];
    }
    return ['default' => 'Funkcja wynikająca z typu jednostki'];
}

function leader_title(array $fields, bool $genitive = false): string
{
    $type = $fields['unit_type'] ?? '';
    $role = $fields['leader_role'] ?? '';
    $gender = $fields['leader_gender'] ?? 'neutral';
    $forms = match ($type) {
        'pack', 'troop' => [['Drużynowy', 'Drużynowa'], ['drużynowego', 'drużynowej']],
        'group' => [['Komendant szczepu', 'Komendantka szczepu'], ['komendanta szczepu', 'komendantki szczepu']],
        'troop_union' => [['Komendant związku drużyn', 'Komendantka związku drużyn'], ['komendanta związku drużyn', 'komendantki związku drużyn']],
        'district' => [['Komendant hufca', 'Komendantka hufca'], ['komendanta hufca', 'komendantki hufca']],
        'circle' => $role === 'chair'
            ? [['Przewodniczący kręgu', 'Przewodnicząca kręgu'], ['przewodniczącego kręgu', 'przewodniczącej kręgu']]
            : [['Komendant kręgu', 'Komendantka kręgu'], ['komendanta kręgu', 'komendantki kręgu']],
        'club' => match ($role) {
            'head' => [['Szef', 'Szefowa'], ['szefa', 'szefowej']],
            'president' => [['Prezes', 'Prezeska'], ['prezesa', 'prezeski']],
            'commodore' => [['Komandor', 'Komandorka'], ['komandora', 'komandorki']],
            default => [['Przewodniczący klubu', 'Przewodnicząca klubu'], ['przewodniczącego klubu', 'przewodniczącej klubu']],
        },
        default => [['Osoba prowadząca', 'Osoba prowadząca'], ['osoby prowadzącej', 'osoby prowadzącej']],
    };
    if ($gender === 'neutral') {
        return $genitive ? 'osoby prowadzącej' : implode(' / ', array_unique($forms[0]));
    }
    return $forms[$genitive ? 1 : 0][$gender === 'female' ? 1 : 0];
}

function point_coordinates($latitude, $longitude): ?array
{
    if (!is_scalar($latitude) || !is_scalar($longitude)) {
        return null;
    }
    $latitude = str_replace(',', '.', trim((string) $latitude));
    $longitude = str_replace(',', '.', trim((string) $longitude));
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        return null;
    }
    $lat = (float) $latitude;
    $lon = (float) $longitude;
    return is_finite($lat) && is_finite($lon) && abs($lat) <= 85 && abs($lon) <= 180 ? [$lat, $lon] : null;
}

function place_coordinates(array $meta): ?array
{
    return point_coordinates($meta['latitude'] ?? '', $meta['longitude'] ?? '') ?: coordinates((string) ($meta['url'] ?? ''));
}

function unit_type_label(array $fields): string
{
    $type = $fields['unit_type'] ?? '';
    if ($type === 'troop') {
        $methods = $fields['methodologies'] ?? [];
        if (count($methods) > 1 || !empty($fields['legacy_multilevel'])) {
            return 'Drużyna Wielopoziomowa';
        }
        return match ($methods[0] ?? '') {
            'cub' => 'Gromada Zuchowa',
            'scout' => 'Drużyna Harcerska',
            'older_scout' => 'Drużyna Starszoharcerska',
            'rover' => 'Drużyna Wędrownicza',
            default => 'Drużyna',
        };
    }
    if ($type === 'circle') {
        return 'Krąg '.mb_strtolower(circle_types()[$fields['circle_type'] ?? ''] ?? '');
    }
    if ($type === 'club' && ($fields['club_type'] ?? '') === 'rescue_group') {
        return 'Harcerska Grupa Ratownicza';
    }
    return unit_types()[$type] ?? 'Jednostka';
}

function unit_filter_keys(array $fields): array
{
    $keys = [];
    foreach ($fields['methodologies'] ?? [] as $method) {
        $key = ['cub' => 'zuchy', 'scout' => 'harcerze', 'older_scout' => 'starsi', 'rover' => 'wedrownicy'][$method] ?? '';
        if ($key !== '') {
            $keys[] = $key;
        }
    }
    if (count($fields['methodologies'] ?? []) > 1 || !empty($fields['legacy_multilevel'])) {
        $keys[] = 'wielopoziomowe';
    }
    return array_values(array_unique($keys));
}

function unit_classification(array $fields): string
{
    if ($fields['unit_type'] === 'pack') {
        return 'zuchy';
    }
    if ($fields['unit_type'] === 'troop') {
        $keys = unit_filter_keys($fields);
        return in_array('wielopoziomowe', $keys, true) ? 'wielopoziomowe' : ($keys[0] ?? 'troops');
    }
    return ['club' => 'clubs', 'circle' => 'circles', 'group' => 'groups', 'troop_union' => 'troop-unions', 'district' => 'districts'][$fields['unit_type']] ?? 'units';
}

function classification_defaults(): array
{
    return [
        'zuchy' => ['Gromady Zuchowe', '#a78014'], 'harcerze' => ['Drużyny Harcerskie', '#547449'],
        'starsi' => ['Drużyny Starszoharcerskie', '#348594'], 'wedrownicy' => ['Drużyny Wędrownicze', '#a84250'],
        'wielopoziomowe' => ['Drużyny Wielopoziomowe', '#766797'], 'troops' => ['Drużyny', '#547449'],
        'clubs' => ['Kluby', '#516f84'], 'circles' => ['Kręgi', '#7c6952'], 'groups' => ['Szczepy', '#52684c'],
        'troop-unions' => ['Związki Drużyn', '#62627f'], 'districts' => ['Hufce', '#35457a'],
    ];
}

function classification_term(string $slug): ?\WP_Term
{
    $existing = get_term_by('slug', $slug, KIND);
    if ($existing) {
        if (!metadata_exists('term', $existing->term_id, '_botud_order')) {
            update_term_meta($existing->term_id, '_botud_order', (int) array_search($slug, array_keys(classification_defaults()), true) + 1);
        }
        return $existing;
    }
    $defaults = classification_defaults()[$slug] ?? null;
    if (!$defaults) {
        return null;
    }
    $result = wp_insert_term($defaults[0], KIND, ['slug' => $slug]);
    if (is_wp_error($result)) {
        return null;
    }
    update_term_meta($result['term_id'], '_botud_color', $defaults[1]);
    update_term_meta($result['term_id'], '_botud_order', (int) array_search($slug, array_keys(classification_defaults()), true) + 1);
    return get_term($result['term_id'], KIND);
}

function sanitize_organization(array $input, array $previous = []): array
{
    $type = organization_choice($input['unit_type'] ?? null, unit_types());
    $methods = array_values(array_intersect(array_keys(methodologies()), array_filter((array) ($input['methodologies'] ?? []), 'is_string')));
    if ($type === 'troop' && $methods === ['cub']) {
        $type = 'pack';
    }
    if ($type === 'pack') {
        $methods = ['cub'];
    } elseif ($type !== 'troop') {
        $methods = [];
    }
    $clean = [
        'unit_type' => $type, 'methodologies' => $methods,
        'club_type' => $type === 'club' ? organization_choice($input['club_type'] ?? null, club_types()) : '',
        'circle_type' => $type === 'circle' ? organization_choice($input['circle_type'] ?? null, circle_types()) : '',
        'specialty' => in_array($type, ['club', 'troop'], true) ? organization_choice($input['specialty'] ?? null, specialties()) : '',
        'leader_gender' => in_array($input['leader_gender'] ?? '', ['female', 'male', 'neutral'], true) ? $input['leader_gender'] : 'neutral',
    ];
    $roles = leader_roles($type);
    $clean['leader_role'] = organization_choice($input['leader_role'] ?? null, $roles, array_key_first($roles));
    if ($clean['club_type'] === 'rescue_group') {
        $clean['specialty'] = 'rescue';
    }
    $clean['legacy_multilevel'] = $type === 'troop' && !$methods && !empty($previous['legacy_multilevel']);
    $point = point_coordinates($input['latitude'] ?? '', $input['longitude'] ?? '');
    $clean['latitude'] = $point ? (string) $point[0] : '';
    $clean['longitude'] = $point ? (string) $point[1] : '';
    return $clean;
}

function organization_choice($value, array $choices, string $default = ''): string
{
    return is_string($value) && isset($choices[$value]) ? $value : $default;
}

function unit_input_errors(array $raw, array $clean, bool $publishing): array
{
    $errors = [];
    $has_coordinate = ($raw['latitude'] ?? '') !== '' || ($raw['longitude'] ?? '') !== '';
    if ($has_coordinate && !point_coordinates($raw['latitude'] ?? '', $raw['longitude'] ?? '')) {
        $errors[] = 'Wpisz obie poprawne współrzędne: szerokość od -85 do 85 i długość od -180 do 180.';
    }
    if (!$publishing) {
        return $errors;
    }
    if (!$clean['unit_type']) {
        $errors[] = 'Wybierz typ jednostki.';
    }
    if ($clean['unit_type'] === 'troop' && !$clean['methodologies'] && !$clean['legacy_multilevel']) {
        $errors[] = 'Wybierz co najmniej jedną metodykę drużyny.';
    }
    if ($clean['unit_type'] === 'club' && !$clean['club_type']) {
        $errors[] = 'Wybierz rodzaj klubu.';
    }
    if ($clean['unit_type'] === 'club' && $clean['club_type'] !== 'rescue_group' && !$clean['specialty']) {
        $errors[] = 'Wybierz specjalność klubu.';
    }
    if ($clean['unit_type'] === 'circle' && !$clean['circle_type']) {
        $errors[] = 'Wybierz rodzaj kręgu.';
    }
    if ($clean['specialty'] === 'other' && empty($clean['specialty_name'])) {
        $errors[] = 'Podaj nazwę innej zatwierdzonej specjalności.';
    }
    return $errors;
}
