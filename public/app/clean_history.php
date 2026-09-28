<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'functions.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$client = generatClient('es');

// === 1. Удаление записей старше 30 дней из Elasticsearch ===
$indices = ['message_history', 'message_history_manager'];

foreach ($indices as $index) {
    $params = [
        'index' => $index,
        'body' => [
            'query' => [
                'range' => [
                    'created_at' => [
                        'lt' => 'now-30d/d' // Записи старше 30 дней
                    ]
                ]
            ]
        ]
    ];

    try {
        $response = $client->deleteByQuery($params);
        $deleted = $response['deleted'] ?? 0;
        echo date('[Y-m-d H:i:s]') . " Index {$index}: deleted {$deleted} docs.\n";
    } catch (Throwable $e) {
        echo date('[Y-m-d H:i:s]') . " Error in {$index}: " . $e->getMessage() . "\n";
    }
}

// Принудительное высвобождение места на диске в Elasticsearch
try {
    $client->indices()->forcemerge([
        'index' => 'message_history,message_history_manager',
        'only_expunge_deletes' => true
    ]);
} catch (Throwable $e) {
    // Игнорируем, если индексы пустые
}

// === 2. Удаление старых файлов из /uploads/ ===
$uploadsDir = __DIR__ . '/uploads/';

if (is_dir($uploadsDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    $now = time();
    $expireSeconds = 30 * 86400; // 30 дней

    foreach ($files as $file) {
        if ($file->isFile() && ($now - $file->getMTime()) > $expireSeconds) {
            unlink($file->getRealPath());
        } elseif ($file->isDir()) {
            // Удаляем пустые директории пользователей
            @rmdir($file->getRealPath());
        }
    }
    echo date('[Y-m-d H:i:s]') . " Disk uploads cleanup finished.\n";
}