<?php

/**
 * Plugin Name: Atlas Jednostek
 * Description: Typy jednostek ZHP, metodyki, kontakty i miejsca działania na mapie.
 * Version: 1.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: kaadeusz.pl
 * Author URI: https://kaadeusz.pl/
 * License: GPL-2.0-or-later
 * Text Domain: atlas-jednostek
 * Update URI: false
 */

namespace Kaadeusz\AtlasJednostek;

defined('ABSPATH') || exit;
const VERSION = '1.0';
const ASSET_VERSION = '1.0';
const SCHEMA_VERSION = 3;
const SCHEMA_OPTION = 'botud_schema_version';
const TYPE = 'botud_unit';
const PLACE = 'botud_place';
const KIND = 'botud_kind';
const OPTION = 'botud_settings';
const META = '_botud_unit';
const TERM_META = '_botud_place';

require_once __DIR__.'/includes/organization.php';
require_once __DIR__.'/includes/model.php';
require_once __DIR__.'/includes/icons.php';
require_once __DIR__.'/includes/migrations.php';
require_once __DIR__.'/includes/admin.php';
require_once __DIR__.'/includes/location-picker.php';
require_once __DIR__.'/includes/editor.php';
require_once __DIR__.'/includes/settings.php';
require_once __DIR__.'/includes/list-table.php';
require_once __DIR__.'/includes/map.php';
require_once __DIR__.'/includes/render.php';

add_action('init', __NAMESPACE__.'\\register_content');
add_shortcode('atlas_jednostek', __NAMESPACE__.'\\render_directory');
add_shortcode('bot_unit_directory', __NAMESPACE__.'\\render_directory');
function asset_version(string $relative): string
{
    return substr(hash_file('sha256', __DIR__.'/'.$relative), 0, 12);
}

add_action('wp_enqueue_scripts', static function (): void {
    // A small scoped stylesheet in the head avoids unstyled shortcode content.
    wp_enqueue_style('atlas-jednostek', plugins_url('assets/directory.css', __FILE__), [], asset_version('assets/directory.css'));
    wp_register_script('atlas-jednostek', plugins_url('assets/directory.js', __FILE__), [], asset_version('assets/directory.js'), true);
    wp_register_script('atlas-jednostek-filters', plugins_url('assets/filter-controls.js', __FILE__), ['atlas-jednostek'], asset_version('assets/filter-controls.js'), true);
});

register_activation_hook(__FILE__, static function (): void {
    register_content();
    migrate_schema();
});
