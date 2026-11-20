<?php
/**
 * Alta y edición de clientes desde recepción.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN, ROL_RECEPCION]);

$id = (int) ($_GET['id'] ?? 0);
$cliente = [
    'name'   => '',
    'email'  => '',
    'phone'  => '',
    'active' => 1,
];

if ($id > 0) {
    $stmt = $pdo->prepare(
        "SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id AND r.name = 'Customer'"
    );
    $stmt->execute(['id' => $id]);
    $encontrado = $stmt->fetch();

    if (!$encontrado) {
        mensaje('error', 'Ese cliente no existe o pertenece a otro rol.');
        redirigir('clientes.php');
    }
    $cliente = $encontrado;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $cliente['name']   = trim($_POST['nombre'] ?? '');
    $cliente['email']  = trim($_POST['correo'] ?? '');
    $cliente['phone']  = trim($_POST['telefono'] ?? '');
    $cliente['active'] = isset($_POST['activo']) ? 1 : 0;
    $clave             = $_POST['clave'] ?? '';

    if (mb_strlen($cliente['name']) < 3) {
        $error = 'Escribe el nombre completo del cliente.';
    } elseif (!filter_var($cliente['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } elseif ($clave !== '' && mb_strlen($clave) < 8) {
        $error = 'La contraseña necesita al menos 8 caracteres.';
    } elseif ($id === 0 && $clave === '') {
        $error = 'Asigna una contraseña inicial para que el cliente pueda entrar.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :correo AND id <> :id');
        $stmt->execute(['correo' => $cliente['email'], 'id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'Ese correo ya pertenece a otra cuenta.';
        } elseif ($id > 0) {
            $sql = 'UPDATE users SET name = :nombre, email = :correo, phone = :telefono, active = :activo';
            $params = [
                'nombre'   => $cliente['name'],
                'correo'   => $cliente['email'],
                'telefono' => $cliente['phone'] !== '' ? $cliente['phone'] : null,
                'activo'   => $cliente['active'],
                'id'       => $id,
            ];
            if ($clave !== '') {
                $sql .= ', password = :clave';
                $params['clave'] = password_hash($clave, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            mensaje('ok', 'Datos del cliente actualizados.');
        } else {
            $rol = $pdo->query("SELECT id FROM roles WHERE name = 'Customer' LIMIT 1")->fetchColumn();
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, role_id, phone, active)
                 VALUES (:nombre, :correo, :clave, :rol, :telefono, :activo)'
            );
            $stmt->execute([
                'nombre'   => $cliente['name'],
                'correo'   => $cliente['email'],
                'clave'    => password_hash($clave, PASSWORD_DEFAULT),
                'rol'      => $rol,
                'telefono' => $cliente['phone'] !== '' ? $cliente['phone'] : null,
                'activo'   => $cliente['active'],
            ]);
            mensaje('ok', 'Cliente registrado. Comunícale su correo y contraseña inicial.');
        }

        redirigir('clientes.php');
    }
}

$titulo = $id > 0 ? 'Editar cliente' : 'Nuevo cliente';
$activo = 'clientes';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">Los clientes pueden reservar por su cuenta; aquí recepción completa o corrige sus datos.</p>
    </div>
    <div class="pagina-acciones">
        <a href="clientes.php" class="btn-panel btn-panel-linea">&larr; Volver a clientes</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="rejilla-formulario">
    <form method="post" class="bloque formulario-panel" novalidate>
        <?= csrf_campo() ?>

        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" value="<?= e($cliente['name']) ?>" maxlength="50" required>
        </div>

        <div class="campo">
            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo" value="<?= e($cliente['email']) ?>" maxlength="100" required>
        </div>

        <div class="campo">
            <label for="telefono">Teléfono</label>
            <input type="tel" id="telefono" name="telefono" value="<?= e($cliente['phone']) ?>" maxlength="20">
        </div>

        <div class="campo">
            <label for="clave">Contraseña <?= $id > 0 ? '(opcional)' : 'inicial' ?></label>
            <input type="password" id="clave" name="clave" minlength="8" autocomplete="new-password" <?= $id === 0 ? 'required' : '' ?>>
            <p class="ayuda"><?= $id > 0 ? 'Déjala en blanco para conservar la actual.' : 'Mínimo 8 caracteres. El cliente puede cambiarla después.' ?></p>
        </div>

        <label class="check">
            <input type="checkbox" name="activo" value="1" <?= (int) $cliente['active'] === 1 ? 'checked' : '' ?>>
            <span>Cuenta activa (puede iniciar sesión y reservar)</span>
        </label>

        <div class="formulario-pie">
            <button type="submit" class="btn-panel btn-panel-acento"><?= $id > 0 ? 'Guardar cambios' : 'Registrar cliente' ?></button>
            <a href="clientes.php" class="btn-panel btn-panel-linea">Cancelar</a>
        </div>
    </form>

    <aside class="bloque nota-lateral">
        <p class="sobrelinea">Recepción</p>
        <h2>Antes de guardar</h2>
        <p>Verifica el correo: es el usuario con el que el huésped entra al sistema.</p>
        <p>Si desactivas la cuenta, la persona no podrá iniciar sesión, pero sus reservas se conservan.</p>
        <p>Las contraseñas se guardan cifradas. Nadie puede leerlas, ni siquiera el administrador.</p>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
