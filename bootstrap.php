<?php

// Fin de mise en place de autoload

use Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

// initialisation de la libraie vlucas/phpdotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();