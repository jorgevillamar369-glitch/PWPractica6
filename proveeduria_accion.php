<?php
/**
 * Acepta o rechaza ofertas y marca necesidades como recibidas.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN, ROL_GERENTE]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('proveeduria.php');
}

verificar_csrf();

$accion = $_POST['accion'] ?? '';
$id     = (int) ($_POST['id'] ?? 0);

if ($accion === 'aceptar_oferta') {
    $stmt = $pdo->prepare(
        'SELECT o.*, s.status AS estado_necesidad, s.id AS necesidad_id
         FROM supply_offers o JOIN supplies s ON s.id = o.supply_id
         WHERE o.id = :id'
    );
    $stmt->execute(['id' => $id]);
    $oferta = $stmt->fetch();

    if (!$oferta || $oferta['status'] !== 'pending') {
        mensaje('aviso', 'Esa oferta ya no está disponible.');
    } elseif (!in_array($oferta['estado_necesidad'], ['requested', 'offered'], true)) {
        mensaje('aviso', 'La necesidad ya está cerrada.');
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE supply_offers SET status = 'rejected' WHERE supply_id = :necesidad AND id <> :id");
            $stmt->execute(['necesidad' => $oferta['necesidad_id'], 'id' => $id]);

            $stmt = $pdo->prepare("UPDATE supply_offers SET status = 'accepted' WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $stmt = $pdo->prepare("UPDATE supplies SET supplier_id = :proveedor, status = 'offered' WHERE id = :id");
            $stmt->execute(['proveedor' => $oferta['supplier_id'], 'id' => $oferta['necesidad_id']]);

            $pdo->commit();
            mensaje('ok', 'Oferta aceptada. La necesidad quedó asignada al proveedor.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            mensaje('error', 'No se pudo aceptar la oferta. Inténtalo de nuevo.');
        }
    }

    redirigir('proveeduria_detalle.php', ['id' => $oferta['necesidad_id'] ?? $id]);
}

if ($accion === 'rechazar_oferta') {
    $stmt = $pdo->prepare('SELECT * FROM supply_offers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $oferta = $stmt->fetch();

    if (!$oferta || $oferta['status'] !== 'pending') {
        mensaje('aviso', 'Esa oferta ya fue revisada.');
    } else {
        $stmt = $pdo->prepare("UPDATE supply_offers SET status = 'rejected' WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM supply_offers WHERE supply_id = :id AND status = 'accepted'");
        $stmt->execute(['id' => $oferta['supply_id']]);
        $aceptadas = (int) $stmt->fetchColumn();

        if ($aceptadas === 0) {
            $stmt = $pdo->prepare("UPDATE supplies SET supplier_id = NULL, status = 'requested' WHERE id = :id AND status = 'offered'");
            $stmt->execute(['id' => $oferta['supply_id']]);
        }

        mensaje('ok', 'Oferta rechazada.');
    }

    redirigir('proveeduria_detalle.php', ['id' => $oferta['supply_id'] ?? 0]);
}

if ($accion === 'recibir') {
    $stmt = $pdo->prepare('SELECT * FROM supplies WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $necesidad = $stmt->fetch();

    if (!$necesidad || $necesidad['status'] !== 'offered') {
        mensaje('aviso', 'Solo se pueden recibir pedidos con oferta aceptada.');
    } else {
        $stmt = $pdo->prepare("UPDATE supplies SET status = 'received' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        mensaje('ok', 'Pedido marcado como recibido. Queda cerrado.');
    }

    redirigir('proveeduria.php');
}

mensaje('error', 'Acción no reconocida.');
redirigir('proveeduria.php');
