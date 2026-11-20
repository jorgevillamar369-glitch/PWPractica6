<?php
/**
 * Panel de inicio: un resumen distinto para cada rol.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_login();

$u   = usuario_actual();
$rol = rol_actual();
$hoy = date('Y-m-d');

$saludo = (int) date('G') < 12 ? 'Buenos días' : ((int) date('G') < 19 ? 'Buenas tardes' : 'Buenas noches');
$nombreCorto = explode(' ', $u['nombre'])[0];

$stats      = [];
$reservas   = [];
$llegadas   = [];
$salidas    = [];
$necesidades = [];
$misOfertas = [];
$proxima    = null;

if (in_array($rol, [ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION], true)) {
    $disponibles = (int) $pdo->query('SELECT COUNT(*) FROM rooms WHERE is_available = 1')->fetchColumn();
    $totalHab    = (int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    $activas     = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('pending', 'confirmed')")->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT b.room_id) FROM bookings b
         WHERE b.status = 'confirmed' AND :hoy1 >= b.check_in_date AND :hoy2 < b.check_out_date"
    );
    $stmt->execute(['hoy1' => $hoy, 'hoy2' => $hoy]);
    $ocupadas = (int) $stmt->fetchColumn();

    $stats = [
        ['Habitaciones libres', $disponibles, 'de ' . $totalHab . ' en total', 'cama'],
        ['Reservas activas', $activas, 'pendientes y confirmadas', 'calendario'],
        ['Ocupación de hoy', $totalHab ? round($ocupadas / $totalHab * 100) . '%' : '0%', $ocupadas === 1 ? '1 habitación ocupada' : $ocupadas . ' habitaciones ocupadas', 'panel'],
        ['Llegadas de hoy', 0, 'huéspedes por recibir', 'reloj'],
    ];

    $stmt = $pdo->prepare(
        "SELECT b.*, u.name AS cliente, r.room_number, r.room_type
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN rooms r ON r.id = b.room_id
         WHERE b.check_in_date = :hoy AND b.status IN ('pending', 'confirmed')
         ORDER BY b.id DESC"
    );
    $stmt->execute(['hoy' => $hoy]);
    $llegadas = $stmt->fetchAll();
    $stats[3][1] = count($llegadas);

    $stmt = $pdo->prepare(
        "SELECT b.*, u.name AS cliente, r.room_number, r.room_type
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN rooms r ON r.id = b.room_id
         WHERE b.check_out_date = :hoy AND b.status = 'confirmed'"
    );
    $stmt->execute(['hoy' => $hoy]);
    $salidas = $stmt->fetchAll();

    $reservas = $pdo->query(
        "SELECT b.*, u.name AS cliente, r.room_number, r.room_type
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN rooms r ON r.id = b.room_id
         ORDER BY b.created_at DESC
         LIMIT 6"
    )->fetchAll();

    if ($rol === ROL_ADMIN) {
        $stats[] = ['Usuarios', (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(), 'de los cinco roles', 'usuarios'];
    } else {
        $stats[] = ['Provisiones abiertas', (int) $pdo->query("SELECT COUNT(*) FROM supplies WHERE status = 'requested'")->fetchColumn(), 'necesidades sin oferta', 'caja'];
    }
}

if ($rol === ROL_CLIENTE) {
    $stmt = $pdo->prepare(
        "SELECT b.*, r.room_number, r.room_type, r.room_price
         FROM bookings b JOIN rooms r ON r.id = b.room_id
         WHERE b.user_id = :id AND b.status IN ('pending', 'confirmed') AND b.check_out_date >= :hoy
         ORDER BY b.check_in_date ASC LIMIT 1"
    );
    $stmt->execute(['id' => $u['id'], 'hoy' => $hoy]);
    $proxima = $stmt->fetch() ?: null;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = :id');
    $stmt->execute(['id' => $u['id']]);
    $totalReservas = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = :id AND status = 'completed'");
    $stmt->execute(['id' => $u['id']]);
    $estancias = (int) $stmt->fetchColumn();

    $stats = [
        ['Reservas hechas', $totalReservas, 'desde que creaste la cuenta', 'calendario'],
        ['Estancias completadas', $estancias, 'gracias por volver', 'check'],
        ['Próxima llegada', $proxima ? fecha_corta($proxima['check_in_date']) : '—', $proxima ? 'Habitación ' . $proxima['room_number'] : 'sin reservas activas', 'reloj'],
    ];

    $stmt = $pdo->prepare(
        "SELECT b.*, r.room_number, r.room_type
         FROM bookings b JOIN rooms r ON r.id = b.room_id
         WHERE b.user_id = :id ORDER BY b.booking_date DESC, b.id DESC LIMIT 5"
    );
    $stmt->execute(['id' => $u['id']]);
    $reservas = $stmt->fetchAll();
}

if ($rol === ROL_PROVEEDOR) {
    $abiertas = (int) $pdo->query("SELECT COUNT(*) FROM supplies WHERE status = 'requested'")->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM supply_offers WHERE supplier_id = :id');
    $stmt->execute(['id' => $u['id']]);
    $totalOfertas = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM supply_offers WHERE supplier_id = :id AND status = 'accepted'");
    $stmt->execute(['id' => $u['id']]);
    $aceptadas = (int) $stmt->fetchColumn();

    $stats = [
        ['Necesidades abiertas', $abiertas, 'puedes ofertar por ellas', 'caja'],
        ['Ofertas enviadas', $totalOfertas, 'en total', 'lapiz'],
        ['Ofertas aceptadas', $aceptadas, 'pedidos en curso', 'check'],
    ];

    $necesidades = $pdo->query(
        "SELECT s.*, (SELECT COUNT(*) FROM supply_offers o WHERE o.supply_id = s.id) AS ofertas
         FROM supplies s
         WHERE s.status = 'requested'
         ORDER BY s.needed_by ASC LIMIT 5"
    )->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT o.*, s.name, s.quantity, s.status AS estado_provision
         FROM supply_offers o JOIN supplies s ON s.id = o.supply_id
         WHERE o.supplier_id = :id
         ORDER BY o.created_at DESC LIMIT 5"
    );
    $stmt->execute(['id' => $u['id']]);
    $misOfertas = $stmt->fetchAll();
}

$titulo = 'Panel';
$activo = 'panel';
include __DIR__ . '/includes/header.php';
?>

<section class="panel-bienvenida">
    <div>
        <p class="sobrelinea"><?= e(ucfirst(fecha_larga($hoy))) ?></p>
        <h2><?= e($saludo) ?>, <?= e($nombreCorto) ?>.</h2>
        <p class="bienvenida-texto">
            <?php if ($rol === ROL_CLIENTE): ?>
                Revisa tus próximas fechas o busca una habitación para la siguiente escapada.
            <?php elseif ($rol === ROL_PROVEEDOR): ?>
                Estas son las necesidades del hotel que todavía no tienen proveedor.
            <?php elseif ($rol === ROL_RECEPCION): ?>
                Empieza por las llegadas de hoy y las habitaciones que quedan libres.
            <?php elseif ($rol === ROL_GERENTE): ?>
                Ocupación, llegadas y provisiones del hotel en una sola vista.
            <?php else: ?>
                Control general del sistema: personas, habitaciones y operación del día.
            <?php endif; ?>
        </p>
    </div>
    <div class="bienvenida-acciones">
        <?php if ($rol === ROL_CLIENTE): ?>
            <a href="disponibilidad.php" class="btn-panel btn-panel-acento">Buscar habitación <?= icono('flecha') ?></a>
        <?php elseif ($rol === ROL_PROVEEDOR): ?>
            <a href="proveeduria.php" class="btn-panel btn-panel-acento">Ver necesidades <?= icono('flecha') ?></a>
        <?php elseif ($rol === ROL_ADMIN): ?>
            <a href="usuario_form.php" class="btn-panel btn-panel-acento">Nuevo usuario <?= icono('mas') ?></a>
        <?php else: ?>
            <a href="reserva_form.php" class="btn-panel btn-panel-acento">Nueva reserva <?= icono('mas') ?></a>
        <?php endif; ?>
    </div>
</section>

<div class="rejilla-stats">
    <?php foreach ($stats as [$etiqueta, $valor, $detalle, $icono]): ?>
        <article class="stat">
            <span class="stat-icono"><?= icono($icono) ?></span>
            <p class="stat-etiqueta"><?= e($etiqueta) ?></p>
            <p class="stat-valor"><?= e((string) $valor) ?></p>
            <p class="stat-detalle"><?= e($detalle) ?></p>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($rol === ROL_CLIENTE): ?>
    <div class="rejilla-dos">
        <section class="bloque">
            <div class="bloque-cabeza">
                <h2>Tu próxima estancia</h2>
                <a href="reservas.php" class="enlace-flecha">Ver todas <?= icono('flecha') ?></a>
            </div>

            <?php if ($proxima): ?>
                <div class="estancia">
                    <div class="estancia-fecha">
                        <span><?= date('M', strtotime($proxima['check_in_date'])) ?></span>
                        <strong><?= date('j', strtotime($proxima['check_in_date'])) ?></strong>
                    </div>
                    <div class="estancia-datos">
                        <p class="estancia-tipo">Habitación <?= (int) $proxima['room_number'] ?> · <?= e($proxima['room_type']) ?></p>
                        <h3><?= fecha_corta($proxima['check_in_date']) ?> — <?= fecha_corta($proxima['check_out_date']) ?></h3>
                        <p class="estancia-meta"><?= noches($proxima['check_in_date'], $proxima['check_out_date']) ?> noches · <?= (int) $proxima['guests'] ?> huéspedes · <?= dinero($proxima['total']) ?></p>
                        <?php [$etiqueta, $clase] = estado_reserva($proxima['status']); ?>
                        <span class="etiqueta etiqueta-<?= $clase ?>"><?= e($etiqueta) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="bloque-vacio">
                    <p>No tienes reservas activas. Las fechas de Ayampe se llenan rápido en temporada alta.</p>
                    <a href="disponibilidad.php" class="btn-panel">Ver disponibilidad</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="bloque">
            <div class="bloque-cabeza">
                <h2>Historial reciente</h2>
            </div>
            <?php if ($reservas): ?>
                <ul class="lista-limpia">
                    <?php foreach ($reservas as $r): ?>
                        <?php [$etiqueta, $clase] = estado_reserva($r['status']); ?>
                        <li>
                            <span>
                                <strong>Hab. <?= (int) $r['room_number'] ?> · <?= e($r['room_type']) ?></strong>
                                <small><?= fecha_corta($r['check_in_date']) ?> — <?= fecha_corta($r['check_out_date']) ?></small>
                            </span>
                            <span class="etiqueta etiqueta-<?= $clase ?>"><?= e($etiqueta) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="texto-suave">Todavía no hay reservas registradas.</p>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($rol === ROL_PROVEEDOR): ?>
    <div class="rejilla-dos">
        <section class="bloque">
            <div class="bloque-cabeza">
                <h2>Necesidades sin proveedor</h2>
                <a href="proveeduria.php" class="enlace-flecha">Ver todas <?= icono('flecha') ?></a>
            </div>
            <?php if ($necesidades): ?>
                <ul class="lista-limpia">
                    <?php foreach ($necesidades as $s): ?>
                        <li>
                            <span>
                                <strong><?= e($s['name']) ?></strong>
                                <small><?= (int) $s['quantity'] ?> unidades · para el <?= fecha_corta($s['needed_by']) ?> · <?= (int) $s['ofertas'] ?> ofertas</small>
                            </span>
                            <a href="oferta_form.php?id=<?= (int) $s['id'] ?>" class="btn-panel btn-panel-mini">Ofertar</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="texto-suave">No hay necesidades abiertas en este momento.</p>
            <?php endif; ?>
        </section>

        <section class="bloque">
            <div class="bloque-cabeza">
                <h2>Tus últimas ofertas</h2>
            </div>
            <?php if ($misOfertas): ?>
                <ul class="lista-limpia">
                    <?php foreach ($misOfertas as $o): ?>
                        <?php [$etiqueta, $clase] = estado_oferta($o['status']); ?>
                        <li>
                            <span>
                                <strong><?= e($o['name']) ?></strong>
                                <small><?= dinero($o['price']) ?> por unidad · <?= fecha_corta($o['created_at']) ?></small>
                            </span>
                            <span class="etiqueta etiqueta-<?= $clase ?>"><?= e($etiqueta) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="texto-suave">Aún no has enviado ofertas.</p>
            <?php endif; ?>
        </section>
    </div>

<?php else: ?>
    <div class="rejilla-dos">
        <section class="bloque">
            <div class="bloque-cabeza">
                <h2>Llegadas de hoy</h2>
                <a href="reservas.php" class="enlace-flecha">Ver reservas <?= icono('flecha') ?></a>
            </div>

            <?php if ($llegadas): ?>
                <div class="tabla-envoltura">
                    <table class="tabla">
                        <thead>
                            <tr><th>Huésped</th><th>Habitación</th><th>Fechas</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($llegadas as $r): ?>
                                <?php [$etiqueta, $clase] = estado_reserva($r['status']); ?>
                                <tr>
                                    <td><?= e($r['cliente']) ?></td>
                                    <td><?= (int) $r['room_number'] ?> · <?= e($r['room_type']) ?></td>
                                    <td><?= fecha_corta($r['check_in_date']) ?> — <?= fecha_corta($r['check_out_date']) ?></td>
                                    <td><span class="etiqueta etiqueta-<?= $clase ?>"><?= e($etiqueta) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="texto-suave">No hay llegadas programadas para hoy.</p>
            <?php endif; ?>

            <?php if ($salidas): ?>
                <p class="nota-bloque"><?= count($salidas) ?> <?= count($salidas) === 1 ? 'salida confirmada' : 'salidas confirmadas' ?> para hoy. Revisa el check-out antes de las 11:00.</p>
            <?php endif; ?>
        </section>

        <section class="bloque">
            <div class="bloque-cabeza">
                <h2>Últimas reservas</h2>
            </div>
            <ul class="lista-limpia">
                <?php foreach ($reservas as $r): ?>
                    <?php [$etiqueta, $clase] = estado_reserva($r['status']); ?>
                    <li>
                        <span>
                            <strong><?= e($r['cliente']) ?></strong>
                            <small>Hab. <?= (int) $r['room_number'] ?> · <?= fecha_corta($r['check_in_date']) ?> al <?= fecha_corta($r['check_out_date']) ?> · <?= dinero($r['total']) ?></small>
                        </span>
                        <span class="etiqueta etiqueta-<?= $clase ?>"><?= e($etiqueta) ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$reservas): ?>
                    <li><span class="texto-suave">Sin reservas todavía.</span></li>
                <?php endif; ?>
            </ul>
        </section>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
