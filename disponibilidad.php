<?php
require_once __DIR__ . '/includes/auth.php';

$hoy = date('Y-m-d');
$desde    = trim($_GET['desde'] ?? '');
$hasta    = trim($_GET['hasta'] ?? '');
$personas = (int) ($_GET['personas'] ?? 2);
$personas = max(1, min(5, $personas));
$error    = '';

if ($desde === '' || $hasta === '') {
    $desde = date('Y-m-d', strtotime('+1 day'));
    $hasta = date('Y-m-d', strtotime('+3 days'));
} elseif (!fecha_es_valida($desde) || !fecha_es_valida($hasta)) {
    $error = 'Revisa las fechas: usa el calendario del formulario.';
} elseif ($desde < $hoy) {
    $error = 'La llegada no puede ser anterior a hoy.';
} elseif ($hasta <= $desde) {
    $error = 'La salida tiene que ser posterior a la llegada.';
}

if ($error !== '') {
    $desde = date('Y-m-d', strtotime('+1 day'));
    $hasta = date('Y-m-d', strtotime('+3 days'));
}

$sql = "SELECT r.*
        FROM rooms r
        WHERE r.is_available = 1
          AND r.capacity >= :personas
          AND NOT EXISTS (
              SELECT 1 FROM bookings b
              WHERE b.room_id = r.id
                AND b.status IN ('pending', 'confirmed')
                AND b.check_in_date < :hasta
                AND b.check_out_date > :desde
          )
        ORDER BY r.room_price, r.room_number";

$stmt = $pdo->prepare($sql);
$stmt->execute(['personas' => $personas, 'desde' => $desde, 'hasta' => $hasta]);
$disponibles = $stmt->fetchAll();

$totalNoches = noches($desde, $hasta);
$titulo = 'Disponibilidad · ' . APP_NOMBRE;
$pagina = 'disponibilidad';

include __DIR__ . '/includes/sitio_header.php';
?>

<main class="pagina-interior">
    <section class="cabecera-interior">
        <div class="contenedor">
            <p class="sobrelinea claro">Reserva directa · sin comisiones</p>
            <h1>Habitaciones disponibles</h1>
            <p class="cabecera-dato"><?= fecha_corta($desde) ?> — <?= fecha_corta($hasta) ?> · <?= $totalNoches ?> <?= $totalNoches === 1 ? 'noche' : 'noches' ?> · <?= $personas ?> <?= $personas === 1 ? 'huésped' : 'huéspedes' ?></p>
        </div>
    </section>

    <div class="contenedor">
        <?php if ($error !== ''): ?>
            <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="buscador buscador-interior" action="disponibilidad.php" method="get">
            <div class="buscador-campo">
                <label for="desde">Llegada</label>
                <input type="date" id="desde" name="desde" value="<?= e($desde) ?>" min="<?= $hoy ?>" required>
            </div>
            <div class="buscador-campo">
                <label for="hasta">Salida</label>
                <input type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
            <div class="buscador-campo">
                <label for="personas">Huéspedes</label>
                <select id="personas" name="personas">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= $i === $personas ? 'selected' : '' ?>><?= $i ?> <?= $i === 1 ? 'persona' : 'personas' ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn-sitio btn-sitio-oscuro">Actualizar búsqueda</button>
        </form>

        <div class="resultado-cabeza">
            <p><strong><?= count($disponibles) ?></strong> <?= count($disponibles) === 1 ? 'habitación libre' : 'habitaciones libres' ?> para esas fechas</p>
            <p class="resultado-nota">Precio por habitación y noche, con desayuno. El pago se hace en recepción.</p>
        </div>

        <?php if (!$disponibles): ?>
            <div class="estado-vacio">
                <p class="sobrelinea">Sin disponibilidad</p>
                <h2>No queda nada para esas fechas</h2>
                <p>Prueba con otras fechas o escríbenos a <a href="mailto:reservas@hotelmangle.ec">reservas@hotelmangle.ec</a>. A veces se libera una habitación de último momento.</p>
                <a href="index.php#habitaciones" class="btn-sitio btn-sitio-oscuro">Volver a las habitaciones</a>
            </div>
        <?php else: ?>
            <div class="lista-disponibles">
                <?php foreach ($disponibles as $h): ?>
                    <?php $total = (float) $h['room_price'] * $totalNoches; ?>
                    <article class="fila-habitacion">
                        <div class="fila-foto">
                            <img src="<?= e(foto_habitacion($h['image'])) ?>" alt="Habitación <?= e($h['room_type']) ?> número <?= (int) $h['room_number'] ?>" loading="lazy">
                        </div>
                        <div class="fila-datos">
                            <p class="fila-tipo"><?= e($h['room_type']) ?> · Habitación <?= (int) $h['room_number'] ?></p>
                            <h2><?= e($h['description'] ?: 'Habitación exterior con baño privado.') ?></h2>
                            <p class="fila-meta">Hasta <?= (int) $h['capacity'] ?> personas · Baño privado · Desayuno incluido</p>
                        </div>
                        <div class="fila-precio">
                            <p class="precio"><?= dinero($h['room_price']) ?><small>/ noche</small></p>
                            <p class="fila-total"><?= dinero($total) ?> por <?= $totalNoches ?> <?= $totalNoches === 1 ? 'noche' : 'noches' ?></p>
                            <a class="btn-sitio btn-sitio-acento" href="reserva_form.php?habitacion=<?= (int) $h['id'] ?>&amp;desde=<?= e($desde) ?>&amp;hasta=<?= e($hasta) ?>&amp;personas=<?= $personas ?>">Reservar</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/sitio_footer.php'; ?>
