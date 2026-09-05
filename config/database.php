<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'podologia_salao';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $connection = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $connection;
}
