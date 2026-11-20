<?php
/**
 * Directorio de clientes. Lo usan administración y recepción.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN, ROL_RECEPCION]);

$buscar = trim($_GET['q'] ?? '');

$sql = "SELECT u.*, COUNT(b.id) AS reservas, MAX(b.check_in_date) AS ultima
        FROM users u
        JOIN roles r ON r.id = u.role_id
        LEFT JOIN bookings b ON b.user_id = u.id
        WHERE r.name = 'Customer'";
$params = [];

if ($buscar !== '') {
    $sql .= ' AND (u.name LIKE :q1 OR u.email LIKE :q2 OR u.phone LIKE :q3)';
    $params = ['q1' => '%' . $buscar . '%', 'q2' => '%' . $buscar . '%', 'q3' => '%' . $buscar . '%'];
}

$sql .= ' GROUP BY u.id ORDER BY u.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$titulo = 'Clientes';
$activo = 'clientes';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">Datos e historial de los clientes. Recepción corrige teléfonos y correos.</p>
        <p class="pagina-dato"><strong><?= count($clientes) ?></strong> <?= count($clientes) === 1 ? 'cliente registrado' : 'clientes registrados' ?></p>
    </div>
    <div class="pagina-acciones">
        <a href="cliente_form.php" class="btn-panel btn-panel-acento"><?= icono('mas') ?> Nuevo cliente</a>
    </div>
</div>

<form class="filtros" method="get" action="clientes.php">
    <div class="campo campo-buscar">
        <label for="q">Buscar cliente</label>
        <input type="search" id="q" name="q" value="<?= e($buscar) ?>" placeholder="Nombre, correo o teléfono">
    </div>
    <button type="submit" class="btn-panel">Buscar</button>
    <?php if ($buscar !== ''): ?>
        <a href="clientes.php" class="btn-panel btn-panel-linea">Limpiar</a>
    <?php endif; ?>
</form>

<div class="bloque">
    <div class="tabla-envoltura">
        <table class="tabla tabla-ancha">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Reservas</th>
                    <th>Última llegada</th>
                    <th>Estado</th>
                    <th class="col-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td>
                            <strong><?= e($c['name']) ?></strong>
                            <small class="celda-nota"><?= e($c['email']) ?></small>
                        </td>
                        <td><?= e($c['phone'] ?: '—') ?></td>
                        <td class="num"><?= (int) $c['reservas'] ?></td>
                        <td><?= $c['ultima'] ? fecha_corta($c['ultima']) : '—' ?></td>
                        <td>
                            <span class="etiqueta etiqueta-<?= (int) $c['active'] === 1 ? 'ok' : 'off' ?>">
                                <?= (int) $c['active'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="col-acciones">
                            <a href="cliente_form.php?id=<?= (int) $c['id'] ?>" class="btn-icono" title="Editar cliente"><?= icono('lapiz') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$clientes): ?>
        <div class="bloque-vacio">
            <p>No se encontraron clientes con ese criterio.</p>
            <a href="cliente_form.php" class="btn-panel">Registrar el primero</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

