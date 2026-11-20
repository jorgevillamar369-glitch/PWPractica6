<?php
/**
 * Oferta de un proveedor para una necesidad concreta.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_PROVEEDOR]);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM supplies WHERE id = :id');
$stmt->execute(['id' => $id]);
$necesidad = $stmt->fetch();

if (!$necesidad) {
    mensaje('error', 'Esa necesidad no existe.');
    redirigir('proveeduria.php');
}

if ($necesidad['status'] !== 'requested') {
    mensaje('aviso', 'Esa necesidad ya tiene una oferta aceptada o fue recibida.');
    redirigir('proveeduria.php');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM supply_offers WHERE supply_id = :id AND supplier_id = :proveedor');
$stmt->execute(['id' => $id, 'proveedor' => usuario_id()]);

if ((int) $stmt->fetchColumn() > 0) {
    mensaje('aviso', 'Ya enviaste una oferta para esta necesidad. Espera la decisión de gerencia.');
    redirigir('proveeduria.php');
}

$error = '';
$precio = '';
$texto  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $precio = (float) ($_POST['precio'] ?? 0);
    $texto  = trim($_POST['mensaje'] ?? '');

    if ($precio <= 0) {
        $error = 'Escribe un precio por unidad mayor que cero.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO supply_offers (supply_id, supplier_id, price, message)
             VALUES (:necesidad, :proveedor, :precio, :mensaje)'
        );
        $stmt->execute([
            'necesidad' => $id,
            'proveedor' => usuario_id(),
            'precio'    => $precio,
            'mensaje'   => $texto !== '' ? $texto : null,
        ]);

        mensaje('ok', 'Oferta enviada. Gerencia la revisará y te avisará por correo.');
        redirigir('proveeduria.php');
    }
}

$titulo = 'Enviar oferta';
$activo = 'proveeduria';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">Indica tu precio por unidad. El total se calcula por la cantidad solicitada.</p>
    </div>
    <div class="pagina-acciones">
        <a href="proveeduria.php" class="btn-panel btn-panel-linea">&larr; Volver a proveeduría</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="rejilla-formulario">
    <form method="post" class="bloque formulario-panel" novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div class="campo">
            <label>Insumo solicitado</label>
            <p class="valor-fijo"><?= e($necesidad['name']) ?> · <?= (int) $necesidad['quantity'] ?> unidades</p>
            <p class="ayuda">Entrega deseada: <?= fecha_larga($necesidad['needed_by']) ?>.</p>
        </div>

        <?php if ($necesidad['notes']): ?>
            <div class="campo">
                <label>Detalle de gerencia</label>
                <p class="valor-fijo"><?= e($necesidad['notes']) ?></p>
            </div>
        <?php endif; ?>

        <div class="campo">
            <label for="precio">Precio por unidad (USD)</label>
            <input type="number" id="precio" name="precio" value="<?= e((string) $precio) ?>" min="0.01" step="0.01" required>
        </div>

        <div class="campo">
            <label for="mensaje">Mensaje para gerencia <span class="opcional">(opcional)</span></label>
            <textarea id="mensaje" name="mensaje" rows="3" maxlength="255" placeholder="Tiempo de entrega, marca o condiciones."><?= e($texto) ?></textarea>
        </div>

        <div class="formulario-pie">
            <button type="submit" class="btn-panel btn-panel-acento">Enviar oferta</button>
            <a href="proveeduria.php" class="btn-panel btn-panel-linea">Cancelar</a>
        </div>
    </form>

    <aside class="bloque resumen-reserva">
        <p class="sobrelinea">Resumen</p>
        <h2><?= e($necesidad['name']) ?></h2>
        <dl class="resumen-cuenta">
            <div><dt>Cantidad</dt><dd><?= (int) $necesidad['quantity'] ?> unidades</dd></div>
            <div><dt>Entrega</dt><dd><?= fecha_corta($necesidad['needed_by']) ?></dd></div>
            <div class="total"><dt>Ofertas recibidas</dt><dd><?= (int) $pdo->query('SELECT COUNT(*) FROM supply_offers WHERE supply_id = ' . (int) $id)->fetchColumn() ?></dd></div>
        </dl>
        <p class="resumen-nota">Gerencia compara precio, plazo y condiciones. Solo se acepta una oferta por necesidad.</p>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
