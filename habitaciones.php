<?php
/**
 * Habitaciones: el gerente las administra; recepción y clientes las consultan.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_login();

$rol         = rol_actual();
$puedeEditar = in_array($rol, [ROL_ADMIN, ROL_GERENTE], true);
$esCliente   = $rol === ROL_CLIENTE;

$habitaciones = $pdo->query('SELECT * FROM rooms ORDER BY room_number')->fetchAll();
$libres = count(array_filter($habitaciones, fn ($h) => (int) $h['is_available'] === 1));

$titulo = 'Habitaciones';
$activo = 'habitaciones';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">
            <?php if ($esCliente): ?>
                Doce habitaciones, tres tipos. Consulta la disponibilidad y elige las fechas que te acomoden.
            <?php elseif ($puedeEditar): ?>
                Altas, precios y estado de las habitaciones. Los cambios se ven de inmediato en el sitio público.
            <?php else: ?>
                Estado actual de las habitaciones para orientar a los huéspedes.
            <?php endif; ?>
        </p>
        <p class="pagina-dato"><strong><?= $libres ?></strong> de <?= count($habitaciones) ?> habitaciones disponibles</p>
    </div>

    <div class="pagina-acciones">
        <?php if ($esCliente): ?>
            <a href="disponibilidad.php" class="btn-panel btn-panel-acento">Buscar por fechas <?= icono('buscar') ?></a>
        <?php elseif ($puedeEditar): ?>
            <a href="habitacion_form.php" class="btn-panel btn-panel-acento"><?= icono('mas') ?> Nueva habitación</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($esCliente): ?>
    <div class="rejilla-cards">
        <?php foreach ($habitaciones as $h): ?>
            <article class="card-habitacion">
                <div class="card-foto">
                    <img src="<?= e(foto_habitacion($h['image'])) ?>" alt="Habitación <?= e($h['room_type']) ?>" loading="lazy">
                    <span class="etiqueta etiqueta-<?= (int) $h['is_available'] === 1 ? 'ok' : 'off' ?>"><?= (int) $h['is_available'] === 1 ? 'Disponible' : 'No disponible' ?></span>
                </div>
                <div class="card-cuerpo">
                    <p class="card-tipo">Habitación <?= (int) $h['room_number'] ?> · <?= e($h['room_type']) ?></p>
                    <h2><?= dinero($h['room_price']) ?><small> / noche</small></h2>
                    <p class="card-texto"><?= e($h['description'] ?: 'Habitación exterior con baño privado.') ?></p>
                    <p class="card-meta">Hasta <?= (int) $h['capacity'] ?> personas · Desayuno incluido</p>
                    <?php if ((int) $h['is_available'] === 1): ?>
                        <a href="disponibilidad.php?personas=<?= (int) $h['capacity'] ?>" class="btn-panel btn-panel-mini">Ver fechas</a>
                    <?php else: ?>
                        <span class="btn-panel btn-panel-mini desactivado">En mantenimiento</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="bloque">
        <div class="bloque-cabeza">
            <h2>Listado de habitaciones</h2>
            <div class="buscador-tabla">
                <?= icono('buscar') ?>
                <input type="search" placeholder="Filtrar por número o tipo" data-filtro-tabla="tablaHabitaciones" aria-label="Filtrar habitaciones">
            </div>
        </div>

        <div class="tabla-envoltura">
            <table class="tabla tabla-ancha" id="tablaHabitaciones">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Capacidad</th>
                        <th>Precio por noche</th>
                        <th>Estado</th>
                        <th class="col-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($habitaciones as $h): ?>
                        <tr>
                            <td><strong><?= (int) $h['room_number'] ?></strong></td>
                            <td><?= e($h['room_type']) ?></td>
                            <td><?= (int) $h['capacity'] ?> personas</td>
                            <td class="num"><?= dinero($h['room_price']) ?></td>
                            <td>
                                <span class="etiqueta etiqueta-<?= (int) $h['is_available'] === 1 ? 'ok' : 'off' ?>">
                                    <?= (int) $h['is_available'] === 1 ? 'Disponible' : 'No disponible' ?>
                                </span>
                            </td>
                            <td class="col-acciones">
                                <?php if ($puedeEditar): ?>
                                    <a href="habitacion_form.php?id=<?= (int) $h['id'] ?>" class="btn-icono" title="Editar"><?= icono('lapiz') ?></a>
                                    <form method="post" action="habitacion_eliminar.php" class="en-linea" data-confirmar="¿Eliminar la habitación <?= (int) $h['room_number'] ?>? Esta acción no se puede deshacer.">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
                                        <button type="submit" class="btn-icono peligro" title="Eliminar"><?= icono('basura') ?></button>
                                    </form>
                                <?php else: ?>
                                    <span class="texto-suave">Solo lectura</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!$habitaciones): ?>
            <p class="texto-suave">Todavía no hay habitaciones registradas.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

