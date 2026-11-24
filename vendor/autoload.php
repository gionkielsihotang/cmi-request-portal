<?php
/**
 * AUTOLOADER & STANDALONE TEST HARNESS
 * File: vendor/autoload.php
 * Mendukung autoloading otomatis untuk kelas Controllers, Entities, Boundaries,
 * serta kelas dasar PHPUnit\Framework\TestCase.
 */

// 1. Matikan warning session di CLI
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// 2. Classmap Autoloader untuk Project CMI
spl_autoload_register(function ($class) {
    // Normalisasi namespace & separator
    $normalized = str_replace('\\', '/', $class);

    // Pemetaan namespace PHPUnit minimal standalone
    if (strpos($class, 'PHPUnit\\Framework\\TestCase') !== false) {
        if (!class_exists('PHPUnit\\Framework\\TestCase', false)) {
            require_once __DIR__ . '/phpunit/src/TestCase.php';
        }
        return;
    }

    $directories = [
        __DIR__ . '/../controllers/pengajuan/',
        __DIR__ . '/../controllers/peninjauan/',
        __DIR__ . '/../controllers/desain/',
        __DIR__ . '/../entities/',
        __DIR__ . '/../boundaries/',
        __DIR__ . '/../config/'
    ];

    $filename = basename($normalized) . '.php';

    foreach ($directories as $dir) {
        $file = $dir . $filename;
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load file konfigurasi database utama
if (file_exists(__DIR__ . '/../config/koneksi.php')) {
    require_once __DIR__ . '/../config/koneksi.php';
}
