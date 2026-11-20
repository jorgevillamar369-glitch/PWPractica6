<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_actual()) {
    redirigir('panel.php');
}

$error  = '';
$nombre = '';
$correo = '';
$telefono = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $clave    = $_POST['clave'] ?? '';
    $repetir  = $_POST['repetir'] ?? '';

    if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 50) {
        $error = 'Escribe tu nombre completo (entre 3 y 50 caracteres).';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } elseif (mb_strlen($clave) < 8) {
        $error = 'La contraseña necesita al menos 8 caracteres.';
    } elseif ($clave !== $repetir) {
        $error = 'Las dos contraseñas no coinciden.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :correo');
        $stmt->execute(['correo' => $correo]);

        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'Ese correo ya tiene una cuenta. Prueba a iniciar sesión.';
        } else {
            $rol = $pdo->query("SELECT id FROM roles WHERE name = 'Customer' LIMIT 1")->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, role_id, phone)
                 VALUES (:nombre, :correo, :clave, :rol, :telefono)'
            );
            $stmt->execute([
                'nombre'   => $nombre,
                'correo'   => $correo,
                'clave'    => password_hash($clave, PASSWORD_DEFAULT),
                'rol'      => $rol,
                'telefono' => $telefono !== '' ? $telefono : null,
            ]);

            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id'     => (int) $pdo->lastInsertId(),
                'nombre' => $nombre,
                'email'  => $correo,
                'rol'    => ROL_CLIENTE,
            ];

            mensaje('ok', 'Tu cuenta está lista. Ya puedes elegir fechas y reservar.');
            redirigir('panel.php');
        }
    }
}

$titulo = 'Crear cuenta · ' . APP_NOMBRE;
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
        <div class="acceso-foto-fondo" style="background-image:url('assets/img/hab1.jpg')"></div>
        <a href="index.php" class="marca-sitio marca-clara">
            <img class="marca-simbolo" src="assets/img/marca-mangle.svg" alt="" width="40" height="40">
            <span class="marca-texto">Hotel Mangle<small>Ayampe · Ecuador</small></span>
        </a>
        <div class="acceso-frase">
            <p class="sobrelinea claro">Tu cuenta de huésped</p>
            <p class="acceso-cita">“Guarda tus fechas en un solo lugar. Cambiar una reserva también es parte del viaje.”</p>
            <p class="acceso-pie">Doce habitaciones · Desayuno incluido</p>
        </div>
    </aside>

    <section class="acceso-panel">
        <div class="acceso-caja acceso-caja-ancha">
            <a href="index.php" class="volver-sitio">&larr; Volver al sitio</a>
            <p class="sobrelinea">Registro de huéspedes</p>
            <h1>Crear una cuenta</h1>
            <p class="acceso-texto">Solo necesitas tus datos para reservar y consultar tus fechas. Las cuentas del personal las crea el administrador.</p>

            <?php if ($error !== ''): ?>
                <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="registro.php" class="formulario" novalidate>
                <?= csrf_campo() ?>

                <div class="campo">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>" maxlength="50" autocomplete="name" required>
                </div>

                <div class="campo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" maxlength="100" autocomplete="email" required>
                </div>

                <div class="campo">
                    <label for="telefono">Teléfono <span class="opcional">(opcional)</span></label>
                    <input type="tel" id="telefono" name="telefono" value="<?= e($telefono) ?>" maxlength="20" autocomplete="tel">
                </div>

                <div class="campo-doble">
                    <div class="campo">
                        <label for="clave">Contraseña</label>
                        <input type="password" id="clave" name="clave" minlength="8" autocomplete="new-password" required>
                        <p class="ayuda">Mínimo 8 caracteres.</p>
                    </div>
                    <div class="campo">
                        <label for="repetir">Repetir contraseña</label>
                        <input type="password" id="repetir" name="repetir" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>

                <button type="submit" class="btn-sitio btn-sitio-acento btn-ancho">Crear cuenta</button>
            </form>

            <p class="acceso-registro">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>.</p>
        </div>
    </section>
</main>

<script src="assets/js/app.js"></script>
</body>
</html>
