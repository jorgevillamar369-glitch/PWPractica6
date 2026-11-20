<?php
/**
 * Detalle de una necesidad y comparación de ofertas.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN, ROL_GERENTE]);

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT s.*, u.name AS proveedor
     FROM supplies s LEFT JOIN users u ON u.id = s.supplier_id
     WHERE s.id = :id'
);
$stmt->execute(['id' => $id]);
$necesidad = $stmt->fetch();

if (!$necesidad) {
    mensaje('error', 'Esa necesidad no existe.');
    redirigir('proveeduria.php');
}

$stmt = $pdo->prepare(
    "SELECT o.*, u.name AS proveedor, u.email
     FROM supply_offers o JOIN users u ON u.id = o.supplier_id
     WHERE o.supply_id = :id
     ORDER BY o.status = 'accepted' DESC, o.price ASC"
);
$stmt->execute(['id' => $id]);
$ofertas = $stmt->fetchAll();

[$etiqueta, $clase] = estado_provision($necesidad['status']);

$titulo = 'Necesidad: ' . $necesidad['name'];
$activo = 'proveeduria';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">Compara precio y condiciones. Al aceptar una oferta, las demás se rechazan automáticamente.</p>
        <p class="pagina-dato"><span class="etiqueta etiqueta-<?= $clase ?>"><?= e($etiqueta) ?></span></p>
    </div>
    <div class="pagina-acciones">
        <a href="proveeduria.php" class="btn-panel btn-panel-linea">&larr; Volver a proveeduría</a>
    </div>
</div>

<div class="rejilla-dos">
    <section class="bloque">
        <div class="bloque-cabeza">
            <h2>Ofertas recibidas</h2>
            <span class="texto-suave"><?= count($ofertas) ?> <?= count($ofertas) === 1 ? 'oferta' : 'ofertas' ?></span>
        </div>

        <?php if ($ofertas): ?>
            <div class="tabla-envoltura">
                <table class="tabla tabla-ancha">
                    <thead>
                        <tr><th>Proveedor</th><th>Precio unidad</th><th>Total</th><th>Mensaje</th><th>Estado</th><th class="col-acciones">Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ofertas as $o): ?>
                            <?php [$etOferta, $clOferta] = estado_oferta($o['status']); ?>
                            <tr>
                                <td>
                                    <strong><?= e($o['proveedor']) ?></strong>
                                    <small class="celda-nota"><?= e($o['email']) ?></small>
                                </td>
                                <td class="num"><?= dinero($o['price']) ?></td>
                                <td class="num"><?= dinero((float) $o['price'] * (int) $necesidad['quantity']) ?></td>
                                <td class="celda-texto"><?= e($o['message'] ?: '—') ?></td>
                                <td><span class="etiqueta etiqueta-<?= $clOferta ?>"><?= e($etOferta) ?></span></td>
                                <td class="col-acciones">
                                    <?php if ($o['status'] === 'pending' && in_array($necesidad['status'], ['requested', 'offered'], true)): ?>
                                        <form method="post" action="proveeduria_accion.php" class="en-linea" data-confirmar="¿Aceptar la oferta de <?= e($o['proveedor']) ?>? Las demás se rechazarán.">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="accion" value="aceptar_oferta">
                                            <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                            <button type="submit" class="btn-panel btn-panel-mini btn-panel-acento"><?= icono('check') ?> Aceptar</button>
                                        </form>
                                        <form method="post" action="proveeduria_accion.php" class="en-linea">
                                            <?= csrf_campo() ?>
                                            <input type="hidden" name="accion" value="rechazar_oferta">
                                            <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                                            <button type="submit" class="btn-panel btn-panel-mini btn-panel-peligro">Rechazar</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="texto-suave">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bloque-vacio">
                <p>Todavía no hay ofertas para esta necesidad.</p>
                <p class="texto-suave">Los proveedores la ven en su panel en cuanto se publica.</p>
            </div>
        <?php endif; ?>
    </section>

    <aside class="bloque resumen-reserva">
        <p class="sobrelinea">Detalle de la necesidad</p>
        <h2><?= e($necesidad['name']) ?></h2>
        <dl class="resumen-cuenta">
            <div><dt>Cantidad</dt><dd><?= (int) $necesidad['quantity'] ?> unidades</dd></div>
            <div><dt>Entrega deseada</dt><dd><?= fecha_corta($necesidad['needed_by']) ?></dd></div>
            <div><dt>Proveedor</dt><dd><?= e($necesidad['proveedor'] ?: 'Sin asignar') ?></dd></div>
            <div><dt>Estado</dt><dd><?= e($etiqueta) ?></dd></div>
        </dl>
        <?php if ($necesidad['notes']): ?>
            <p class="resumen-nota"><?= e($necesidad['notes']) ?></p>
        <?php endif; ?>

        <?php if ($necesidad['status'] === 'offered'): ?>
            <form method="post" action="proveeduria_accion.php" data-confirmar="¿Confirmar que la mercadería llegó a bodega?">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="recibir">
                <input type="hidden" name="id" value="<?= (int) $necesidad['id'] ?>">
                <button type="submit" class="btn-panel btn-panel-acento btn-ancho">Marcar como recibida</button>
            </form>
        <?php elseif ($necesidad['status'] === 'received'): ?>
            <p class="aviso aviso-ok">Pedido recibido y cerrado.</p>
        <?php endif; ?>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

