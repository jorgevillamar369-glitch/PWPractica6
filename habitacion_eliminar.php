<?php
/**
 * Elimina una habitación si no tiene reservas asociadas.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN, ROL_GERENTE]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('habitaciones.php');
}

verificar_csrf();
$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT room_number FROM rooms WHERE id = :id');
$stmt->execute(['id' => $id]);
$numero = $stmt->fetchColumn();

if ($numero === false) {
    mensaje('error', 'La habitación ya no existe.');
    redirigir('habitaciones.php');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE room_id = :id');
$stmt->execute(['id' => $id]);

if ((int) $stmt->fetchColumn() > 0) {
    mensaje('error', 'No se puede eliminar la habitación ' . $numero . ': tiene reservas registradas.');
    redirigir('habitaciones.php');
}

$stmt = $pdo->prepare('DELETE FROM rooms WHERE id = :id');
$stmt->execute(['id' => $id]);

mensaje('ok', 'Habitación ' . $numero . ' eliminada.');
redirigir('habitaciones.php');
