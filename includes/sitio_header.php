<?php
require_once __DIR__ . '/auth.php';
$u = usuario_actual();
$pagina = $pagina ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? APP_NOMBRE . ' · ' . APP_LUGAR) ?></title>
    <meta name="description" content="Hotel Mangle: habitaciones frente al mar en Ayampe, Manabí. Reserva directa, sin comisiones.">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/fuentes.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="sitio">

<header class="sitio-barra">
    <div class="contenedor barra-interior">
        <a href="index.php" class="marca-sitio" aria-label="Hotel Mangle, inicio">
            <img class="marca-simbolo" src="assets/img/marca-mangle.svg" alt="" width="40" height="40">
            <span class="marca-texto">Hotel Mangle<small>Ayampe · Ecuador</small></span>
        </a>

        <nav class="sitio-nav" id="menuSitio" aria-label="Secciones del sitio">
            <a href="index.php#habitaciones">Habitaciones</a>
            <a href="index.php#hotel">El hotel</a>
            <a href="index.php#servicios">Servicios</a>
            <a href="index.php#entorno">El entorno</a>
        </nav>

        <div class="sitio-acciones">
            <?php if ($u): ?>
                <a href="panel.php" class="btn-sitio btn-sitio-claro">Mi panel</a>
            <?php else: ?>
                <a href="login.php" class="enlace-ingresar">Ingresar</a>
                <a href="registro.php" class="btn-sitio btn-sitio-acento">Reservar</a>
            <?php endif; ?>
            <button class="btn-menu-sitio" type="button" aria-controls="menuSitio" aria-expanded="false" data-menu-sitio aria-label="Abrir menú">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
