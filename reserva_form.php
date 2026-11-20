<?php
/**
 * Nueva reserva. El cliente reserva para sí; el personal, para un cliente.
 */

require_once __DIR__ . '/includes/auth.php';

if (!usuario_actual()) {
    $volver = 'reserva_form.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
    mensaje('aviso', 'Inicia sesión para confirmar la reserva.');
    redirigir('login.php', ['volver' => $volver]);
}

if (rol_actual() === ROL_PROVEEDOR) {
    mensaje('error', 'Los proveedores no pueden crear reservas.');
    redirigir('panel.php');
}

$esStaff = es_staff();
$hoy     = date('Y-m-d');

$habitacionId = (int) ($_GET['habitacion'] ?? $_POST['habitacion'] ?? 0);
$desde        = trim($_GET['desde'] ?? $_POST['desde'] ?? '');
$hasta        = trim($_GET['hasta'] ?? $_POST['hasta'] ?? '');
$personas     = (int) ($_GET['personas'] ?? $_POST['personas'] ?? 2);

if ($desde === '' || !fecha_es_valida($desde) || $desde < $hoy) {
    $desde = date('Y-m-d', strtotime('+1 day'));
}
if ($hasta === '' || !fecha_es_valida($hasta) || $hasta <= $desde) {
    $hasta = date('Y-m-d', strtotime($desde . ' +2 days'));
}

$stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = :id');
$stmt->execute(['id' => $habitacionId]);
$habitacion = $stmt->fetch();

if (!$habitacion) {
    mensaje('aviso', 'Elige una habitación disponible para empezar la reserva.');
    redirigir($esStaff ? 'habitaciones.php' : 'disponibilidad.php');
}

$personas = max(1, min((int) $habitacion['capacity'], $personas));
$noches   = noches($desde, $hasta);
$total    = (float) $habitacion['room_price'] * $noches;
$error    = '';
$notas    = '';

$clientes = [];
if ($esStaff) {
    $clientes = $pdo->query(
        "SELECT u.id, u.name, u.email
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE r.name = 'Customer' AND u.active = 1
         ORDER BY u.name"
    )->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $desdePost = trim($_POST['desde'] ?? '');
    $hastaPost = trim($_POST['hasta'] ?? '');
    $personas  = (int) ($_POST['personas'] ?? 1);
    $notas     = trim($_POST['notas'] ?? '');
    $clienteId = $esStaff ? (int) ($_POST['cliente'] ?? 0) : usuario_id();

    if (!fecha_es_valida($desdePost) || $desdePost < $hoy) {
        $error = 'La fecha de llegada no es válida. Elige hoy o una fecha futura.';
    } elseif (!fecha_es_valida($hastaPost) || $hastaPost <= $desdePost) {
        $error = 'La salida debe ser posterior a la llegada.';
    } elseif ($personas < 1 || $personas > (int) $habitacion['capacity']) {
        $error = 'Esta habitación admite hasta ' . (int) $habitacion['capacity'] . ' personas.';
    } elseif (!$esStaff && (int) $habitacion['is_available'] !== 1) {
        $error = 'La habitación no está disponible en este momento.';
    } elseif ($esStaff && $clienteId <= 0) {
        $error = 'Elige el cliente para el que se hace la reserva.';
    } else {
        if ($esStaff) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id
                 WHERE u.id = :id AND r.name = 'Customer' AND u.active = 1"
            );
            $stmt->execute(['id' => $clienteId]);
            if ((int) $stmt->fetchColumn() === 0) {
                $error = 'El cliente seleccionado no es válido.';
            }
        }

        if ($error === '' && !habitacion_libre($pdo, $habitacionId, $desdePost, $hastaPost)) {
            $error = 'Esa habitación ya está reservada en esas fechas. Prueba con otras fechas o con otra habitación.';
        }

        if ($error === '') {
            $noches = noches($desdePost, $hastaPost);
            $total  = (float) $habitacion['room_price'] * $noches;
            $estado = $esStaff ? 'confirmed' : 'pending';

            $stmt = $pdo->prepare(
                'INSERT INTO bookings (user_id, room_id, booking_date, check_in_date, check_out_date, guests, total, status, notes)
                 VALUES (:cliente, :habitacion, :hoy, :desde, :hasta, :personas, :total, :estado, :notas)'
            );
            $stmt->execute([
                'cliente'    => $clienteId,
                'habitacion' => $habitacionId,
                'hoy'        => $hoy,
                'desde'      => $desdePost,
                'hasta'      => $hastaPost,
                'personas'   => $personas,
                'total'      => $total,
                'estado'     => $estado,
                'notas'      => $notas !== '' ? $notas : null,
            ]);

            mensaje('ok', $esStaff
                ? 'Reserva confirmada para el cliente. Habitación ' . $habitacion['room_number'] . '.'
                : 'Reserva registrada. Recepción la confirmará en breve.');
            redirigir('reservas.php');
        }
    }

    $desde = $desdePost;
    $hasta = $hastaPost;
    $noches = noches($desde, $hasta);
    $total = (float) $habitacion['room_price'] * $noches;
}

$titulo = 'Nueva reserva';
$activo = 'reservas';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">Revisa las fechas y los huéspedes antes de guardar. El sistema vuelve a comprobar que la habitación siga libre.</p>
    </div>
    <div class="pagina-acciones">
        <a href="reservas.php" class="btn-panel btn-panel-linea">&larr; Volver a reservas</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<?php if ((int) $habitacion['is_available'] !== 1 && !$esStaff): ?>
    <div class="aviso aviso-info" role="status">Esta habitación está marcada como no disponible. Puedes elegir otra desde el buscador.</div>
<?php endif; ?>

<div class="rejilla-formulario">
    <form method="post" class="bloque formulario-panel" novalidate data-reserva data-precio="<?= e((string) $habitacion['room_price']) ?>">
        <?= csrf_campo() ?>
        <input type="hidden" name="habitacion" value="<?= (int) $habitacion['id'] ?>">

        <?php if ($esStaff): ?>
            <div class="campo">
                <label for="cliente">Cliente</label>
                <select id="cliente" name="cliente" required>
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($_POST['cliente'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?> — <?= e($c['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="ayuda">¿No aparece? <a href="cliente_form.php">Registra un cliente nuevo</a>.</p>
            </div>
        <?php else: ?>
            <div class="campo">
                <label>Huésped</label>
                <p class="valor-fijo"><?= e(usuario_actual()['nombre']) ?> · <?= e(usuario_actual()['email']) ?></p>
            </div>
        <?php endif; ?>

        <div class="campo-doble">
            <div class="campo">
                <label for="desde">Llegada</label>
                <input type="date" id="desde" name="desde" value="<?= e($desde) ?>" min="<?= $hoy ?>" required>
            </div>
            <div class="campo">
                <label for="hasta">Salida</label>
                <input type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                <p class="ayuda">Check-out antes de las 11:00.</p>
            </div>
        </div>

        <div class="campo">
            <label for="personas">Huéspedes</label>
            <select id="personas" name="personas">
                <?php for ($i = 1; $i <= (int) $habitacion['capacity']; $i++): ?>
                    <option value="<?= $i ?>" <?= $i === $personas ? 'selected' : '' ?>><?= $i ?> <?= $i === 1 ? 'persona' : 'personas' ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="campo">
            <label for="notas">Notas para recepción <span class="opcional">(opcional)</span></label>
            <textarea id="notas" name="notas" rows="3" maxlength="255" placeholder="Hora de llegada, cuna, alergias..."><?= e($notas) ?></textarea>
        </div>

        <div class="formulario-pie">
            <button type="submit" class="btn-panel btn-panel-acento"><?= $esStaff ? 'Confirmar reserva' : 'Solicitar reserva' ?></button>
            <a href="reservas.php" class="btn-panel btn-panel-linea">Cancelar</a>
        </div>
    </form>

    <aside class="bloque resumen-reserva">
        <div class="resumen-foto">
            <img src="<?= e(foto_habitacion($habitacion['image'])) ?>" alt="Habitación <?= e($habitacion['room_type']) ?>">
        </div>
        <p class="sobrelinea">Habitación <?= (int) $habitacion['room_number'] ?></p>
        <h2><?= e($habitacion['room_type']) ?></h2>
        <p class="resumen-texto"><?= e($habitacion['description'] ?: 'Habitación exterior con baño privado.') ?></p>
        <p class="resumen-meta">Hasta <?= (int) $habitacion['capacity'] ?> personas · Desayuno incluido</p>

        <dl class="resumen-cuenta">
            <div><dt>Precio por noche</dt><dd><?= dinero($habitacion['room_price']) ?></dd></div>
            <div><dt>Noches</dt><dd id="resumenNoches"><?= $noches ?></dd></div>
            <div class="total"><dt>Total</dt><dd id="resumenTotal"><?= dinero($total) ?></dd></div>
        </dl>

        <p class="resumen-nota"><?= $esStaff ? 'La reserva queda confirmada al guardar.' : 'La reserva queda pendiente hasta que recepción la confirme.' ?> El pago se realiza en el hotel.</p>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
