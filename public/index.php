<?php

declare(strict_types=1);

session_start();

require __DIR__.'/../vendor/autoload.php';

use App\Kernel;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

$app = Kernel::createApp();
$app->run();
