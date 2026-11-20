<?php
/**
 * Alta y edición de usuarios con cualquier rol. Solo administración.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN]);

$rolesDb = $pdo->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();

$id = (int) ($_GET['id'] ?? 0);
$usuario = [
    'name'   => '',
    'email'  => '',
    'phone'  => '',
    'role_id'=> 0,
    'active' => 1,
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $encontrado = $stmt->fetch();

    if (!$encontrado) {
        mensaje('error', 'Ese usuario no existe.');
        redirigir('usuarios.php');
    }
    $usuario = $encontrado;
}

$error = '';
$esUnoMismo = $id > 0 && $id === usuario_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $usuario['name']  = trim($_POST['nombre'] ?? '');
    $usuario['email'] = trim($_POST['correo'] ?? '');
    $usuario['phone'] = trim($_POST['telefono'] ?? '');
    $usuario['active'] = isset($_POST['activo']) ? 1 : 0;
    $clave = $_POST['clave'] ?? '';

    $rolId = (int) ($_POST['rol'] ?? 0);
    $rolValido = false;
    foreach ($rolesDb as $r) {
        if ((int) $r['id'] === $rolId) {
            $rolValido = true;
        }
    }

    if (mb_strlen($usuario['name']) < 3) {
        $error = 'Escribe el nombre completo (mínimo 3 caracteres).';
    } elseif (!filter_var($usuario['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } elseif (!$rolValido) {
        $error = 'Elige un rol de la lista.';
    } elseif ($clave !== '' && mb_strlen($clave) < 8) {
        $error = 'La contraseña necesita al menos 8 caracteres.';
    } elseif ($id === 0 && $clave === '') {
        $error = 'Escribe una contraseña inicial.';
    } elseif ($esUnoMismo && $rolId !== (int) $usuario['role_id']) {
        $error = 'No puedes quitarte el rol de administrador a ti mismo.';
    } else {
        $usuario['role_id'] = $rolId;

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :correo AND id <> :id');
        $stmt->execute(['correo' => $usuario['email'], 'id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'Ese correo ya pertenece a otra cuenta.';
        }

        if ($error === '') {
            $activo = $esUnoMismo ? 1 : $usuario['active'];

            if ($id > 0) {
                $sql = 'UPDATE users SET name = :nombre, email = :correo, phone = :telefono, role_id = :rol, active = :activo';
                $params = [
                    'nombre'   => $usuario['name'],
                    'correo'   => $usuario['email'],
                    'telefono' => $usuario['phone'] !== '' ? $usuario['phone'] : null,
                    'rol'      => $usuario['role_id'],
                    'activo'   => $activo,
                    'id'       => $id,
                ];
                if ($clave !== '') {
                    $sql .= ', password = :clave';
                    $params['clave'] = password_hash($clave, PASSWORD_DEFAULT);
                }
                $sql .= ' WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                mensaje('ok', 'Usuario actualizado.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password, role_id, phone, active)
                     VALUES (:nombre, :correo, :clave, :rol, :telefono, :activo)'
                );
                $stmt->execute([
                    'nombre'   => $usuario['name'],
                    'correo'   => $usuario['email'],
                    'clave'    => password_hash($clave, PASSWORD_DEFAULT),
                    'rol'      => $usuario['role_id'],
                    'telefono' => $usuario['phone'] !== '' ? $usuario['phone'] : null,
                    'activo'   => $activo,
                ]);
                mensaje('ok', 'Usuario creado. Comparte la contraseña por un canal seguro.');
            }

            redirigir('usuarios.php');
        }
    }
}

$titulo = $id > 0 ? 'Editar usuario' : 'Nuevo usuario';
$activo = 'usuarios';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">El rol define el menú y los permisos; se aplican al volver a iniciar sesión.</p>
    </div>
    <div class="pagina-acciones">
        <a href="usuarios.php" class="btn-panel btn-panel-linea">&larr; Volver a usuarios</a>
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
            <input type="text" id="nombre" name="nombre" value="<?= e($usuario['name']) ?>" maxlength="50" required>
        </div>

        <div class="campo">
            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo" value="<?= e($usuario['email']) ?>" maxlength="100" required>
            <p class="ayuda">Es el usuario de acceso al sistema.</p>
        </div>

        <div class="campo">
            <label for="telefono">Teléfono</label>
            <input type="tel" id="telefono" name="telefono" value="<?= e($usuario['phone']) ?>" maxlength="20">
        </div>

        <div class="campo">
            <label for="rol">Rol</label>
            <select id="rol" name="rol" required <?= $esUnoMismo ? 'disabled' : '' ?>>
                <option value="">Selecciona un rol</option>
                <?php foreach ($rolesDb as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" <?= (int) $usuario['role_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                        <?= e(etiqueta_rol($r['name'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($esUnoMismo): ?>
                <input type="hidden" name="rol" value="<?= (int) $usuario['role_id'] ?>">
                <p class="ayuda">No puedes cambiarte el rol a ti mismo.</p>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="clave">Contraseña <?= $id > 0 ? '(opcional)' : 'inicial' ?></label>
            <input type="password" id="clave" name="clave" minlength="8" autocomplete="new-password" <?= $id === 0 ? 'required' : '' ?>>
            <p class="ayuda"><?= $id > 0 ? 'Déjala vacía para conservar la contraseña actual.' : 'Mínimo 8 caracteres.' ?></p>
        </div>

        <label class="check">
            <input type="checkbox" name="activo" value="1" <?= (int) $usuario['active'] === 1 || $esUnoMismo ? 'checked' : '' ?> <?= $esUnoMismo ? 'disabled' : '' ?>>
            <span>Cuenta activa</span>
        </label>
        <?php if ($esUnoMismo): ?><input type="hidden" name="activo" value="1"><?php endif; ?>

        <div class="formulario-pie">
            <button type="submit" class="btn-panel btn-panel-acento"><?= $id > 0 ? 'Guardar cambios' : 'Crear usuario' ?></button>
            <a href="usuarios.php" class="btn-panel btn-panel-linea">Cancelar</a>
        </div>
    </form>

    <aside class="bloque nota-lateral">
        <p class="sobrelinea">Roles del sistema</p>
        <h2>Quién puede hacer qué</h2>
        <ul class="lista-rol">
            <li><strong>Administrador</strong> Gestiona usuarios y supervisa todo.</li>
            <li><strong>Gerente</strong> Habitaciones, precios y proveeduría.</li>
            <li><strong>Recepcionista</strong> Reservas y datos de clientes.</li>
            <li><strong>Cliente</strong> Busca, reserva y ve sus fechas.</li>
            <li><strong>Proveedor</strong> Consulta necesidades y envía ofertas.</li>
        </ul>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
