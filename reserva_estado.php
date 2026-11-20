<?php
/**
 * Cambia el estado de una reserva con las reglas de cada rol.
 * Acciones: confirmar, cancelar, finalizar.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('reservas.php');
}

verificar_csrf();

$id     = (int) ($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';
$esStaff = es_staff();

$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id');
$stmt->execute(['id' => $id]);
$reserva = $stmt->fetch();

if (!$reserva) {
    mensaje('error', 'Esa reserva no existe.');
    redirigir('reservas.php');
}

if (!$esStaff && (int) $reserva['user_id'] !== usuario_id()) {
    mensaje('error', 'No puedes modificar una reserva de otra persona.');
    redirigir('reservas.php');
}

$estadoActual = $reserva['status'];

if ($accion === 'confirmar') {
    if (!$esStaff) {
        mensaje('error', 'Solo recepción puede confirmar una reserva.');
    } elseif ($estadoActual !== 'pending') {
        mensaje('aviso', 'Solo se confirman reservas pendientes.');
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        mensaje('ok', 'Reserva confirmada. El huésped ya puede llegar.');
    }
} elseif ($accion === 'cancelar') {
    if (!in_array($estadoActual, ['pending', 'confirmed'], true)) {
        mensaje('aviso', 'Esa reserva ya está cerrada.');
    } elseif (!$esStaff && $reserva['check_in_date'] <= date('Y-m-d')) {
        mensaje('error', 'La estancia ya comenzó. Llama a recepción para cancelar.');
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        mensaje('ok', 'Reserva cancelada. La habitación vuelve a estar libre.');
    }
} elseif ($accion === 'finalizar') {
    if (!$esStaff) {
        mensaje('error', 'Solo el personal puede cerrar una estancia.');
    } elseif ($estadoActual !== 'confirmed') {
        mensaje('aviso', 'Solo se finalizan reservas confirmadas.');
    } else {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        mensaje('ok', 'Estancia finalizada. La habitación quedó libre.');
    }
} else {
    mensaje('error', 'Acción no reconocida.');
}

redirigir('reservas.php');
