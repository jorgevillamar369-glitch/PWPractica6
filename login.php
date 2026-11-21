<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_actual()) {
    redirigir('panel.php');
}

$volver = trim($_POST['volver'] ?? $_GET['volver'] ?? '');
if (!preg_match('/^[a-z0-9_\-]+\.php(\?[^\s]*)?$/i', $volver) || str_starts_with($volver, 'login.php')) {
    $volver = 'panel.php';
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $correo   = trim($_POST['correo'] ?? '');
    $clave    = $_POST['clave'] ?? '';

    if ($correo === '' || $clave === '') {
        $error = 'Escribe tu correo y tu contraseña.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT u.*, r.name AS rol
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :correo AND u.active = 1
             LIMIT 1'
        );
        $stmt->execute(['correo' => $correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($clave, $usuario['password'])) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id'     => (int) $usuario['id'],
                'nombre' => $usuario['name'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol'],
            ];
            redirigir($volver);
        }

        $error = 'El correo o la contraseña no coinciden. Revisa los datos e inténtalo otra vez.';
    }
}

$titulo = 'Ingresar · ' . APP_NOMBRE;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?></title>
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/fuentes.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="acceso-cuerpo">

<main class="acceso">
    <aside class="acceso-foto">
        <div class="acceso-foto-fondo" style="background-image:url('assets/img/sala.jpg')"></div>
        <a href="index.php" class="marca-sitio marca-clara">
            <img class="marca-simbolo" src="assets/img/marca-mangle.svg" alt="" width="40" height="40">
            <span class="marca-texto">Hotel Mangle<small>Ayampe · Ecuador</small></span>
        </a>
        <div class="acceso-frase">
            <p class="sobrelinea claro">Recepción en línea</p>
            <p class="acceso-cita">“Cada reserva empieza con una fecha y termina con una llave.”</p>
            <p class="acceso-pie">Vía a los Mangles, km 2 · Ayampe, Manabí</p>
        </div>
    </aside>

    <section class="acceso-panel">
        <div class="acceso-caja">
            <a href="index.php" class="volver-sitio">&larr; Volver al sitio</a>
            <p class="sobrelinea">Área privada</p>
            <h1>Entrar al panel</h1>
            <p class="acceso-texto">Un acceso para huéspedes y personal. Cada rol ve solo sus secciones.</p>

            <?php if ($error !== ''): ?>
                <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" class="formulario" novalidate>
                <?= csrf_campo() ?>
                <input type="hidden" name="volver" value="<?= e($volver) ?>">

                <div class="campo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" autocomplete="email" required autofocus>
                </div>

                <div class="campo">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn-sitio btn-sitio-acento btn-ancho">Entrar</button>
            </form>

            <details class="cuentas-prueba">
                <summary>Cuentas de prueba</summary>
                <table>
                    <thead><tr><th>Rol</th><th>Correo</th><th>Contraseña</th></tr></thead>
                    <tbody>
                        <tr><td>Administrador</td><td>admin@hotelmangle.ec</td><td>Admin123!</td></tr>
                        <tr><td>Gerente</td><td>gerente@hotelmangle.ec</td><td>Gerente123!</td></tr>
                        <tr><td>Recepcionista</td><td>recepcion@hotelmangle.ec</td><td>Recepcion123!</td></tr>
                        <tr><td>Cliente</td><td>cliente@hotelmangle.ec</td><td>Cliente123!</td></tr>
                        <tr><td>Proveedor</td><td>proveedor@hotelmangle.ec</td><td>Proveedor123!</td></tr>
                    </tbody>
                </table>
            </details>

            <p class="acceso-registro">¿Todavía no tienes cuenta? <a href="registro.php">Regístrate como cliente</a>.</p>

            <button type="button" class="grupo-acceso" data-grupo aria-haspopup="dialog" aria-controls="grupoDialogo">Grupo 04 · ver integrantes</button>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/grupo_modal.php'; ?>
<script src="assets/js/app.js"></script>
</body>
</html>
