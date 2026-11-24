<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$files = require __DIR__.'/package-files.php';
$errors = [];
foreach ($files as $relative) {
    $path = $root.'/'.$relative;
    if (!is_file($path) || is_link($path)) {
        $errors[] = 'Missing or linked package file: '.$relative;
    }
}
$phpFiles = array_merge(
    array_filter($files, static fn (string $file): bool => str_ends_with($file, '.php')),
    ['tools/package-files.php', 'tools/check.php', 'tools/package.php']
);
foreach ($phpFiles as $relative) {
    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($root.'/'.$relative).' 2>&1', $output, $status);
    if ($status !== 0) {
        $errors[] = implode(PHP_EOL, $output);
    }
}
if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}
echo 'Package files and PHP syntax OK ('.count($phpFiles).' PHP files).'.PHP_EOL;
