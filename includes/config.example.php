<?php
/**
 * Plantilla de configuración para publicar en el hosting.
 * Copiar como config.php y completar con los datos del panel.
 */

declare(strict_types=1);

const APP_NOMBRE = 'Hotel Mangle';
const APP_LUGAR  = 'Ayampe, Manabí';
const APP_DEBUG  = false;

const DB_HOST    = 'localhost';
const DB_PORT    = '3306';
const DB_NOMBRE  = 'if0_00000000_hotelmangle'; // Nombre con prefijo del hosting
const DB_USUARIO = 'if0_00000000';             // Usuario de la base
const DB_CLAVE   = 'CAMBIAR_ESTA_CLAVE';

date_default_timezone_set('America/Guayaquil');

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NOMBRE . ';charset=utf8mb4',
        DB_USUARIO,
        DB_CLAVE,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $error) {
    error_log('Error de conexión: ' . $error->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa includes/config.php.');
}
