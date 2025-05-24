<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$path = __DIR__ . '/' . $_ENV['DB_PATH'];

try {
    $pdo = new PDO('sqlite:' . $path);
    echo "Conectare reușită!";
} catch (PDOException $e) {
    echo "Eroare: " . $e->getMessage();
}
