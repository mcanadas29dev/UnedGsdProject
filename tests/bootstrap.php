<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Cargar variables de entorno del entorno de test si existe
if (file_exists(dirname(__DIR__).'/.env.test.local')) {
    (new Dotenv())->usePutenv()->loadEnv(dirname(__DIR__).'/.env.test.local');
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Configuración de permisos para debug
if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}
