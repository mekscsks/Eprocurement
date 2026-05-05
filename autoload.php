<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/dbcon.php';

// Auto-load all procedural function files
$functionDirs = [
    __DIR__ . '/functions',
    __DIR__ . '/admin/functions',
    __DIR__ . '/superadmin/functions',
    __DIR__ . '/user',
];

foreach ($functionDirs as $dir) {
    if (!is_dir($dir)) continue;
    foreach (glob($dir . '/*.php') as $file) {
        require_once $file;
    }
}

// Class-based autoloader
spl_autoload_register(function ($class) {
    $baseDirs = [
        __DIR__ . '/superadmin/classes',
        __DIR__ . '/admin/classes',
    ];
    $file = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\')) . '.php';
    foreach ($baseDirs as $base) {
        $path = $base . DIRECTORY_SEPARATOR . $file;
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});
