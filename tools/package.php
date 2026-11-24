<?php

declare(strict_types=1);

if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, 'PHP ZipArchive is required.'.PHP_EOL);
    exit(1);
}
$root = dirname(__DIR__);
$files = require __DIR__.'/package-files.php';
foreach ($files as $relative) {
    if (!is_file($root.'/'.$relative) || is_link($root.'/'.$relative)) {
        throw new RuntimeException('Missing or linked package file: '.$relative);
    }
}
$source = file_get_contents($root.'/atlas-jednostek.php');
if (!preg_match('/^ \* Version: ([0-9]+\.[0-9]+\.[0-9]+)$/m', $source, $match)) {
    throw new RuntimeException('Missing semantic version in plugin header.');
}
$version = $match[1];
if (!str_contains($source, "const VERSION = '".$version."';") || !str_contains($source, "const ASSET_VERSION = '".$version."';")) {
    throw new RuntimeException('Plugin and asset versions must match the release version.');
}
if (!str_contains(file_get_contents($root.'/readme.txt'), 'Stable tag: '.$version."\n")) {
    throw new RuntimeException('Stable tag must match the release version.');
}
$directory = $argv[1] ?? $root.'/dist';
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    throw new RuntimeException('Cannot create output directory.');
}
$archive = rtrim($directory, '/\\').'/atlas-jednostek-'.$version.'.zip';
$zip = new ZipArchive();
if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Cannot create release archive.');
}
try {
    foreach ($files as $relative) {
        $path = $root.'/'.$relative;
        if (!is_file($path) || is_link($path)) {
            throw new RuntimeException('Missing or linked package file: '.$relative);
        }
        $entry = 'atlas-jednostek/'.$relative;
        if (!$zip->addFile($path, $entry)) {
            throw new RuntimeException('Cannot add package file: '.$relative);
        }
        $zip->setMtimeName($entry, 315532800);
        $zip->setExternalAttributesName($entry, ZipArchive::OPSYS_UNIX, 0100644 << 16);
    }
} finally {
    $zip->close();
}
echo $archive.PHP_EOL;
echo 'SHA256 '.hash_file('sha256', $archive).PHP_EOL;
