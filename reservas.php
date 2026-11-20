<?php
/**
 * Reservas: el cliente ve las suyas; el personal ve y gestiona todas.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_login();

$rol     = rol_actual();
$esStaff = es_staff();
$hoy     = date('Y-m-d');

$estado = trim($_GET['estado'] ?? '');
$buscar = trim($_GET['q'] ?? '');

if ($esStaff) {
    $where  = [];
    $params = [];

    if (in_array($estado, ['pending', 'confirmed', 'cancelled', 'completed'], true)) {
        $where[] = 'b.status = :estado';
        $params['estado'] = $estado;
    }
    if ($buscar !== '') {
        $where[] = '(u.name LIKE :q1 OR u.email LIKE :q2 OR r.room_number LIKE :q3)';
        $params['q1'] = '%' . $buscar . '%';
        $params['q2'] = '%' . $buscar . '%';
        $params['q3'] = '%' . $buscar . '%';
    }

    $sql = "SELECT b.*, u.name AS cliente, u.email AS correo, r.room_number, r.room_type
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            JOIN rooms r ON r.id = b.room_id";
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY b.check_in_date DESC, b.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll();

    $conteos = $pdo->query(
        "SELECT status, COUNT(*) AS total FROM bookings GROUP BY status"
    )->fetchAll(PDO::FETCH_KEY_PAIR);
} else {
    $stmt = $pdo->prepare(
        "SELECT b.*, r.room_number, r.room_type
         FROM bookings b JOIN rooms r ON r.id = b.room_id
         WHERE b.user_id = :id
         ORDER BY b.check_in_date DESC, b.id DESC"
    );
    $stmt->execute(['id' => usuario_id()]);
    $reservas = $stmt->fetchAll();
    $conteos = [];
}

$titulo = $rol === ROL_CLIENTE ? 'Mis reservas' : 'Reservas';
$activo = 'reservas';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">
            <?php if ($esStaff): ?>
                Confirma llegadas, cancela reservas y cierra estancias.
            <?php else: ?>
                Tus reservas en Ayampe. Puedes cancelar mientras la estancia no haya comenzado.
            <?php endif; ?>
        </p>
        <?php if ($esStaff): ?>
            <div class="chips">
                <span class="chip"><strong><?= (int) ($conteos['pending'] ?? 0) ?></strong> <?= plural((int) ($conteos['pending'] ?? 0), 'pendiente', 'pendientes') ?></span>
                <span class="chip"><strong><?= (int) ($conteos['confirmed'] ?? 0) ?></strong> <?= plural((int) ($conteos['confirmed'] ?? 0), 'confirmada', 'confirmadas') ?></span>
                <span class="chip"><strong><?= (int) ($conteos['completed'] ?? 0) ?></strong> <?= plural((int) ($conteos['completed'] ?? 0), 'finalizada', 'finalizadas') ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="pagina-acciones">
        <?php if ($esStaff): ?>
            <a href="reserva_form.php" class="btn-panel btn-panel-acento"><?= icono('mas') ?> Nueva reserva</a>
        <?php else: ?>
            <a href="disponibilidad.php" class="btn-panel btn-panel-acento"><?= icono('buscar') ?> Buscar habitación</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($esStaff): ?>
    <form class="filtros" method="get" action="reservas.php">
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (['pending' => 'Pendientes', 'confirmed' => 'Confirmadas', 'completed' => 'Finalizadas', 'cancelled' => 'Canceladas'] as $valor => $texto): ?>
                    <option value="<?= e($valor) ?>" <?= $estado === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo campo-buscar">
            <label for="q">Buscar</label>
            <input type="search" id="q" name="q" value="<?= e($buscar) ?>" placeholder="Cliente, correo o habitación">
        </div>
        <button type="submit" class="btn-panel">Filtrar</button>
        <?php if ($estado !== '' || $buscar !== ''): ?>
            <a href="reservas.php" class="btn-panel btn-panel-linea">Limpiar</a>
        <?php endif; ?>
    </form>
<?php endif; ?>

<div class="bloque">
    <div class="tabla-envoltura">
        <table class="tabla tabla-ancha">
            <thead>
                <tr>
                    <?php if ($esStaff): ?><th>Cliente</th><?php endif; ?>
                    <th>Habitación</th>
                    <th>Llegada</th>
                    <th>Salida</th>
                    <th>Noches</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="col-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $r): ?>
                    <?php
                    [$etiqueta, $clase] = estado_reserva($r['status']);
                    $puedeCancelar = in_array($r['status'], ['pending', 'confirmed'], true)
                        && ($esStaff || $r['check_in_date'] > $hoy);
                    ?>
                    <tr>
                        <?php if ($esStaff): ?>
                            <td>
                                <strong><?= e($r['cliente']) ?></strong>
                                <small class="celda-nota"><?= e($r['correo']) ?></small>
                            </td>
                        <?php endif; ?>
                        <td><?= (int) $r['room_number'] ?> · <?= e($r['room_type']) ?></td>
                        <td><?= fecha_corta($r['check_in_date']) ?></td>
                        <td><?= fecha_corta($r['check_out_date']) ?></td>
                        <td class="num"><?= noches($r['check_in_date'], $r['check_out_date']) ?></td>
                        <td class="num"><?= dinero($r['total']) ?></td>
                        <td><span class="etiqueta etiqueta-<?= $clase ?>"><?= e($etiqueta) ?></span></td>
                        <td class="col-acciones">
                            <?php if ($esStaff && $r['status'] === 'pending'): ?>
                                <form method="post" action="reserva_estado.php" class="en-linea">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <input type="hidden" name="accion" value="confirmar">
                                    <button type="submit" class="btn-panel btn-panel-mini btn-panel-acento"><?= icono('check') ?> Confirmar</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($esStaff && $r['status'] === 'confirmed' && $r['check_in_date'] <= $hoy): ?>
                                <form method="post" action="reserva_estado.php" class="en-linea" data-confirmar="¿Cerrar la estancia de <?= e($r['cliente'] ?? 'este cliente') ?>?">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <input type="hidden" name="accion" value="finalizar">
                                    <button type="submit" class="btn-panel btn-panel-mini">Finalizar</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($puedeCancelar): ?>
                                <form method="post" action="reserva_estado.php" class="en-linea" data-confirmar="¿Cancelar esta reserva? La habitación quedará libre.">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <input type="hidden" name="accion" value="cancelar">
                                    <button type="submit" class="btn-panel btn-panel-mini btn-panel-peligro">Cancelar</button>
                                </form>
                            <?php endif; ?>

                            <?php if (!$esStaff && !$puedeCancelar && in_array($r['status'], ['pending', 'confirmed'], true)): ?>
                                <span class="texto-suave">En curso</span>
                            <?php elseif ($esStaff && !in_array($r['status'], ['pending', 'confirmed'], true)): ?>
                                <span class="texto-suave">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$reservas): ?>
        <div class="bloque-vacio">
            <p>No hay reservas con esos filtros.</p>
            <?php if (!$esStaff): ?>
                <a href="disponibilidad.php" class="btn-panel">Buscar fechas</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

