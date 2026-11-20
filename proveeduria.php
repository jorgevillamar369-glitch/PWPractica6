<?php
/**
 * Necesidades del hotel y ofertas de proveedores.
 * Gerencia publica necesidades; el proveedor oferta; gerencia acepta.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN, ROL_GERENTE, ROL_PROVEEDOR]);

$rol         = rol_actual();
$esProveedor = $rol === ROL_PROVEEDOR;
$esStaff     = in_array($rol, [ROL_ADMIN, ROL_GERENTE], true);

$filtro = trim($_GET['estado'] ?? '');

if ($esProveedor) {
    $sql = "SELECT s.*, u.name AS proveedor,
                   (SELECT COUNT(*) FROM supply_offers o WHERE o.supply_id = s.id) AS ofertas,
                   (SELECT o.price FROM supply_offers o WHERE o.supply_id = s.id AND o.supplier_id = :yo LIMIT 1) AS mi_precio,
                   (SELECT o.status FROM supply_offers o WHERE o.supply_id = s.id AND o.supplier_id = :yo2 LIMIT 1) AS mi_estado
            FROM supplies s
            LEFT JOIN users u ON u.id = s.supplier_id
            WHERE s.status IN ('requested', 'offered')
            ORDER BY s.status = 'requested' DESC, s.needed_by ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['yo' => usuario_id(), 'yo2' => usuario_id()]);
    $necesidades = $stmt->fetchAll();
} else {
    $where = '';
    $params = [];
    if (in_array($filtro, ['requested', 'offered', 'received'], true)) {
        $where = 'WHERE s.status = :estado';
        $params['estado'] = $filtro;
    }

    $stmt = $pdo->prepare(
        "SELECT s.*, u.name AS proveedor,
                (SELECT COUNT(*) FROM supply_offers o WHERE o.supply_id = s.id) AS ofertas
         FROM supplies s
         LEFT JOIN users u ON u.id = s.supplier_id
         $where
         ORDER BY s.status = 'requested' DESC, s.needed_by ASC"
    );
    $stmt->execute($params);
    $necesidades = $stmt->fetchAll();
}

$titulo = 'Proveeduría';
$activo = 'proveeduria';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">
            <?php if ($esProveedor): ?>
                Necesidades del hotel. Envía un precio por unidad; gerencia decide.
            <?php else: ?>
                Insumos que necesita el hotel. Publica una necesidad y compara ofertas.
            <?php endif; ?>
        </p>
    </div>
    <div class="pagina-acciones">
        <?php if ($esStaff): ?>
            <a href="necesidad_form.php" class="btn-panel btn-panel-acento"><?= icono('mas') ?> Nueva necesidad</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($esStaff): ?>
    <form class="filtros" method="get" action="proveeduria.php">
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todas</option>
                <?php foreach (['requested' => 'Solicitadas', 'offered' => 'Con oferta', 'received' => 'Recibidas'] as $valor => $texto): ?>
                    <option value="<?= e($valor) ?>" <?= $filtro === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-panel">Filtrar</button>
        <?php if ($filtro !== ''): ?>
            <a href="proveeduria.php" class="btn-panel btn-panel-linea">Limpiar</a>
        <?php endif; ?>
    </form>
<?php endif; ?>

<div class="bloque">
    <div class="bloque-cabeza">
        <h2><?= $esProveedor ? 'Necesidades en curso' : 'Necesidades registradas' ?></h2>
        <span class="texto-suave"><?= count($necesidades) ?> <?= count($necesidades) === 1 ? 'registro' : 'registros' ?></span>
    </div>

    <div class="tabla-envoltura">
        <table class="tabla tabla-ancha">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Cantidad</th>
                    <th>Para cuándo</th>
                    <th>Estado</th>
                    <?php if ($esProveedor): ?>
                        <th>Tu oferta</th>
                        <th class="col-acciones">Acciones</th>
                    <?php else: ?>
                        <th>Proveedor</th>
                        <th>Ofertas</th>
                        <th class="col-acciones">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($necesidades as $s): ?>
                    <?php [$etiqueta, $clase] = estado_provision($s['status']); ?>
                    <tr>
                        <td>
                            <strong><?= e($s['name']) ?></strong>
                            <?php if ($s['notes']): ?><small class="celda-nota"><?= e($s['notes']) ?></small><?php endif; ?>
                        </td>
                        <td class="num"><?= (int) $s['quantity'] ?></td>
                        <td><?= fecha_corta($s['needed_by']) ?></td>
                        <td><span class="etiqueta etiqueta-<?= $clase ?>"><?= e($etiqueta) ?></span></td>

                        <?php if ($esProveedor): ?>
                            <td>
                                <?php if ($s['mi_precio'] !== null): ?>
                                    <?= dinero($s['mi_precio']) ?>
                                    <?php [$etOferta, $clOferta] = estado_oferta((string) $s['mi_estado']); ?>
                                    <small class="celda-nota"><span class="etiqueta etiqueta-<?= $clOferta ?>"><?= e($etOferta) ?></span></small>
                                <?php else: ?>
                                    <span class="texto-suave">Sin oferta</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-acciones">
                                <?php if ($s['status'] === 'requested' && $s['mi_precio'] === null): ?>
                                    <a href="oferta_form.php?id=<?= (int) $s['id'] ?>" class="btn-panel btn-panel-mini btn-panel-acento">Ofertar</a>
                                <?php elseif ($s['mi_precio'] !== null): ?>
                                    <span class="texto-suave">Enviada</span>
                                <?php else: ?>
                                    <span class="texto-suave">—</span>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td><?= e($s['proveedor'] ?: 'Sin asignar') ?></td>
                            <td class="num"><?= (int) $s['ofertas'] ?></td>
                            <td class="col-acciones">
                                <a href="proveeduria_detalle.php?id=<?= (int) $s['id'] ?>" class="btn-panel btn-panel-mini">Ver ofertas</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$necesidades): ?>
        <div class="bloque-vacio">
            <p>No hay necesidades con ese estado.</p>
            <?php if ($esStaff): ?>
                <a href="necesidad_form.php" class="btn-panel">Publicar la primera</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

