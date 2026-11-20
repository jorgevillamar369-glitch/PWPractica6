<?php
/**
 * Elimina una cuenta solo si no tiene historial asociado.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('usuarios.php');
}

verificar_csrf();
$id = (int) ($_POST['id'] ?? 0);

if ($id === usuario_id()) {
    mensaje('error', 'No puedes eliminar tu propia cuenta.');
    redirigir('usuarios.php');
}

$stmt = $pdo->prepare('SELECT u.*, r.name AS rol FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id');
$stmt->execute(['id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    mensaje('error', 'Ese usuario ya no existe.');
    redirigir('usuarios.php');
}

if ($usuario['rol'] === ROL_ADMIN) {
    $admins = (int) $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'Administrator'")->fetchColumn();
    if ($admins <= 1) {
        mensaje('error', 'No se puede eliminar al único administrador del sistema.');
        redirigir('usuarios.php');
    }
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = :id');
$stmt->execute(['id' => $id]);
$reservas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM supply_offers WHERE supplier_id = :id');
$stmt->execute(['id' => $id]);
$ofertas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM supplies WHERE supplier_id = :id');
$stmt->execute(['id' => $id]);
$provisiones = (int) $stmt->fetchColumn();

if ($reservas + $ofertas + $provisiones > 0) {
    mensaje('error', 'La cuenta de ' . $usuario['name'] . ' tiene historial (reservas u ofertas). Desactívala en lugar de eliminarla.');
    redirigir('usuarios.php');
}

$stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
$stmt->execute(['id' => $id]);

mensaje('ok', 'Cuenta de ' . $usuario['name'] . ' eliminada.');
redirigir('usuarios.php');
