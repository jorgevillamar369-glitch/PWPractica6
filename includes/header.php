<?php
/**
 * Cabecera del panel interno.
 * Antes de incluirla se define $titulo y, si aplica, $activo.
 */

require_once __DIR__ . '/auth.php';
exigir_login();

$u      = usuario_actual();
$titulo = $titulo ?? 'Panel';
$activo = $activo ?? 'panel';

$menu = [['panel.php', 'Panel', 'panel', [ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION, ROL_CLIENTE, ROL_PROVEEDOR]]];

if (in_array(rol_actual(), [ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION, ROL_CLIENTE], true)) {
    $menu[] = ['habitaciones.php', rol_actual() === ROL_CLIENTE ? 'Habitaciones' : 'Habitaciones', 'cama', [ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION, ROL_CLIENTE]];
}
if (in_array(rol_actual(), [ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION, ROL_CLIENTE], true)) {
    $menu[] = ['reservas.php', rol_actual() === ROL_CLIENTE ? 'Mis reservas' : 'Reservas', 'calendario', [ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION, ROL_CLIENTE]];
}
if (in_array(rol_actual(), [ROL_ADMIN, ROL_RECEPCION], true)) {
    $menu[] = ['clientes.php', 'Clientes', 'usuarios', [ROL_ADMIN, ROL_RECEPCION]];
}
if (rol_actual() === ROL_ADMIN) {
    $menu[] = ['usuarios.php', 'Usuarios', 'usuarios', [ROL_ADMIN]];
}
$menu[] = ['proveeduria.php', 'Proveeduría', 'caja', [ROL_ADMIN, ROL_GERENTE, ROL_PROVEEDOR]];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= APP_NOMBRE ?></title>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/fuentes.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="panel-cuerpo">

<div class="offcanvas-lg offcanvas-start barra-lateral" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralTitulo">
    <div class="barra-marca">
        <a href="panel.php" class="marca-panel" id="menuLateralTitulo">
            <span class="marca-simbolo">M</span>
            <span>Hotel Mangle<small>Ayampe · Manabí</small></span>
        </a>
        <button type="button" class="btn-cerrar-menu d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar menú">
            <?= icono('equis') ?>
        </button>
    </div>

    <nav class="menu-panel" aria-label="Menú principal">
        <?php foreach ($menu as [$archivo, $texto, $icono, $roles]): ?>
            <?php if (in_array(rol_actual(), $roles, true)): ?>
                <a href="<?= e($archivo) ?>" class="menu-enlace <?= $activo === basename($archivo, '.php') ? 'activo' : '' ?>">
                    <?= icono($icono) ?><span><?= e($texto) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="barra-pie">
        <div class="usuario-caja">
            <span class="usuario-inicial"><?= e(mb_substr($u['nombre'], 0, 1)) ?></span>
            <span>
                <strong><?= e($u['nombre']) ?></strong>
                <small><?= e(etiqueta_rol($u['rol'])) ?></small>
            </span>
        </div>
        <a href="logout.php" class="menu-enlace salir"><?= icono('salir') ?><span>Cerrar sesión</span></a>
    </div>
</div>

<div class="panel-principal">
    <header class="barra-superior">
        <button class="btn-menu d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>

        <div class="barra-titulo">
            <p class="miga"><?= e(etiqueta_rol(rol_actual())) ?> · <?= date('d/m/Y') ?></p>
            <h1><?= e($titulo) ?></h1>
        </div>

        <div class="barra-acciones">
            <a href="index.php" class="enlace-sitio">Ver el sitio <?= icono('flecha') ?></a>
            <span class="usuario-chip" title="<?= e($u['nombre']) ?>"><?= e(mb_substr($u['nombre'], 0, 1)) ?></span>
        </div>
    </header>

    <main class="panel-contenido">
        <?php mostrar_mensaje(); ?>
