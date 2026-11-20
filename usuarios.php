<?php
/**
 * Gestión de todas las cuentas. Solo administración.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN]);

$buscar = trim($_GET['q'] ?? '');
$rolFiltro = trim($_GET['rol'] ?? '');

$sql = 'SELECT u.*, r.name AS rol
        FROM users u JOIN roles r ON r.id = u.role_id
        WHERE 1 = 1';
$params = [];

if ($buscar !== '') {
    $sql .= ' AND (u.name LIKE :q1 OR u.email LIKE :q2)';
    $params['q1'] = '%' . $buscar . '%';
    $params['q2'] = '%' . $buscar . '%';
}
if (in_array($rolFiltro, [ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION, ROL_CLIENTE, ROL_PROVEEDOR], true)) {
    $sql .= ' AND r.name = :rol';
    $params['rol'] = $rolFiltro;
}
$sql .= ' ORDER BY r.id, u.name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$totalPorRol = $pdo->query(
    'SELECT r.name, COUNT(u.id) AS total FROM roles r LEFT JOIN users u ON u.role_id = r.id GROUP BY r.id ORDER BY r.id'
)->fetchAll();

$titulo = 'Usuarios';
$activo = 'usuarios';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">Altas, roles y estado de las cuentas. Cada rol ve solo sus secciones.</p>
        <div class="chips">
            <?php foreach ($totalPorRol as $t): ?>
                <span class="chip"><strong><?= (int) $t['total'] ?></strong> <?= e(etiqueta_rol($t['name'])) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="pagina-acciones">
        <a href="usuario_form.php" class="btn-panel btn-panel-acento"><?= icono('mas') ?> Nuevo usuario</a>
    </div>
</div>

<form class="filtros" method="get" action="usuarios.php">
    <div class="campo campo-buscar">
        <label for="q">Buscar</label>
        <input type="search" id="q" name="q" value="<?= e($buscar) ?>" placeholder="Nombre o correo">
    </div>
    <div class="campo">
        <label for="rol">Rol</label>
        <select id="rol" name="rol">
            <option value="">Todos</option>
            <?php foreach ([ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION, ROL_CLIENTE, ROL_PROVEEDOR] as $r): ?>
                <option value="<?= e($r) ?>" <?= $rolFiltro === $r ? 'selected' : '' ?>><?= e(etiqueta_rol($r)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn-panel">Filtrar</button>
    <?php if ($buscar !== '' || $rolFiltro !== ''): ?>
        <a href="usuarios.php" class="btn-panel btn-panel-linea">Limpiar</a>
    <?php endif; ?>
</form>

<div class="bloque">
    <div class="tabla-envoltura">
        <table class="tabla tabla-ancha">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Teléfono</th>
                    <th>Alta</th>
                    <th>Estado</th>
                    <th class="col-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usr): ?>
                    <tr>
                        <td>
                            <strong><?= e($usr['name']) ?></strong>
                            <small class="celda-nota"><?= e($usr['email']) ?></small>
                        </td>
                        <td><?= e(etiqueta_rol($usr['rol'])) ?></td>
                        <td><?= e($usr['phone'] ?: '—') ?></td>
                        <td><?= fecha_corta($usr['created_at']) ?></td>
                        <td>
                            <span class="etiqueta etiqueta-<?= (int) $usr['active'] === 1 ? 'ok' : 'off' ?>">
                                <?= (int) $usr['active'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="col-acciones">
                            <a href="usuario_form.php?id=<?= (int) $usr['id'] ?>" class="btn-icono" title="Editar"><?= icono('lapiz') ?></a>
                            <?php if ((int) $usr['id'] !== usuario_id()): ?>
                                <form method="post" action="usuario_eliminar.php" class="en-linea" data-confirmar="¿Eliminar la cuenta de <?= e($usr['name']) ?>? Si tiene historial, el sistema no lo permitirá.">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $usr['id'] ?>">
                                    <button type="submit" class="btn-icono peligro" title="Eliminar"><?= icono('basura') ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$usuarios): ?>
        <div class="bloque-vacio">
            <p>No hay usuarios con esos filtros.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

