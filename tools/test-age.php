<?php

declare(strict_types=1);

define('ABSPATH', __DIR__);
require dirname(__DIR__).'/includes/organization.php';

$cases = [
    [['unit_type' => 'pack', 'age' => '99 lat'], '6–10 lat'],
    [['unit_type' => 'troop', 'methodologies' => ['scout'], 'age' => '99 lat'], '10–13 lat'],
    [['unit_type' => 'troop', 'methodologies' => ['older_scout']], '13–16 lat'],
    [['unit_type' => 'troop', 'methodologies' => ['rover']], '15–21 lat'],
    [['unit_type' => 'troop', 'methodologies' => ['cub', 'scout', 'older_scout']], '6–16 lat'],
    [['unit_type' => 'troop', 'methodologies' => ['older_scout', 'rover', 'older_scout']], '13–21 lat'],
    [['unit_type' => 'troop', 'legacy_multilevel' => true, 'age' => '6–21 lat'], ''],
    [['unit_type' => 'troop', 'methodologies' => ['unknown']], ''],
    [['unit_type' => 'circle', 'age' => '21+'], '21+'],
    [['unit_type' => 'club'], ''],
];
foreach ($cases as [$fields, $expected]) {
    $actual = Kaadeusz\AtlasJednostek\unit_age_label($fields);
    if ($actual !== $expected) {
        throw new RuntimeException('Unexpected age label: '.$actual.'; expected '.$expected);
    }
}
echo 'Methodology age rules OK ('.count($cases).' cases).'.PHP_EOL;
