<?php
/**
 * Publica una necesidad de insumos del hotel.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN, ROL_GERENTE]);

$error = '';
$datos = [
    'name'      => '',
    'quantity'  => '',
    'needed_by' => date('Y-m-d', strtotime('+7 days')),
    'notes'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $datos['name']      = trim($_POST['nombre'] ?? '');
    $datos['quantity']  = (int) ($_POST['cantidad'] ?? 0);
    $datos['needed_by'] = trim($_POST['fecha'] ?? '');
    $datos['notes']     = trim($_POST['notas'] ?? '');

    if (mb_strlen($datos['name']) < 3) {
        $error = 'Escribe el nombre del insumo.';
    } elseif ($datos['quantity'] <= 0) {
        $error = 'La cantidad debe ser mayor que cero.';
    } elseif (!fecha_es_valida($datos['needed_by']) || $datos['needed_by'] < date('Y-m-d')) {
        $error = 'Elige una fecha de entrega válida, de hoy en adelante.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO supplies (name, quantity, status, needed_by, notes)
             VALUES (:nombre, :cantidad, 'requested', :fecha, :notas)"
        );
        $stmt->execute([
            'nombre'   => $datos['name'],
            'cantidad' => $datos['quantity'],
            'fecha'    => $datos['needed_by'],
            'notas'    => $datos['notes'] !== '' ? $datos['notes'] : null,
        ]);

        mensaje('ok', 'Necesidad publicada. Los proveedores ya pueden verla y ofertar.');
        redirigir('proveeduria.php');
    }
}

$titulo = 'Nueva necesidad';
$activo = 'proveeduria';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">Indica insumo, cantidad y fecha. La necesidad queda visible para todos los proveedores.</p>
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

        <div class="campo">
            <label for="nombre">Insumo</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($datos['name']) ?>" maxlength="50" placeholder="Ej.: Toallas de baño" required>
        </div>

        <div class="campo-doble">
            <div class="campo">
                <label for="cantidad">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" value="<?= e((string) $datos['quantity']) ?>" min="1" required>
            </div>
            <div class="campo">
                <label for="fecha">Entrega deseada</label>
                <input type="date" id="fecha" name="fecha" value="<?= e($datos['needed_by']) ?>" min="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="campo">
            <label for="notas">Detalle <span class="opcional">(opcional)</span></label>
            <textarea id="notas" name="notas" rows="3" maxlength="255" placeholder="Marca, medida, empaque o cualquier condición."><?= e($datos['notes']) ?></textarea>
        </div>

        <div class="formulario-pie">
            <button type="submit" class="btn-panel btn-panel-acento">Publicar necesidad</button>
            <a href="proveeduria.php" class="btn-panel btn-panel-linea">Cancelar</a>
        </div>
    </form>

    <aside class="bloque nota-lateral">
        <p class="sobrelinea">Cómo funciona</p>
        <h2>De la necesidad al pedido</h2>
        <p>Al publicar, la necesidad aparece como <strong>solicitada</strong>. Los proveedores envían su precio por unidad.</p>
        <p>Cuando aceptas una oferta, el insumo pasa a <strong>con oferta</strong> y queda asignado a ese proveedor. Las demás ofertas se rechazan.</p>
        <p>Cuando llegue, marca la necesidad como <strong>recibida</strong>.</p>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
