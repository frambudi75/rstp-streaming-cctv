<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

try {
    echo "Menghubungkan ke MySQL Server di " . DB_HOST . ":" . DB_PORT . "...\n";
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    echo "Mengeksekusi database/schema.sql...\n";
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schemaSql);

    echo "Mengeksekusi database/seeders.sql...\n";
    $seedersSql = file_get_contents(__DIR__ . '/seeders.sql');
    $pdo->exec($seedersSql);

    echo "Migrasi database dan seeder data awal BERHASIL dijalankan!\n";
} catch (Throwable $e) {
    echo "Gagal migrasi: " . $e->getMessage() . "\n";
    echo "Pastikan modul MySQL di XAMPP Control Panel sudah dalam status 'Running'.\n";
    exit(1);
}

