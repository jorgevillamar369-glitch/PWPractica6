<?php
/**
 * Alta y edición de habitaciones. Solo administración y gerencia.
 */

require_once __DIR__ . '/includes/auth.php';
exigir_roles([ROL_ADMIN, ROL_GERENTE]);

$tipos = ['Doble', 'Suite', 'Familiar'];
$imagenes = [
    'hab1.jpg' => 'Doble con balcón',
    'hab2.jpg' => 'Doble con sala',
    'sala.jpg' => 'Suite con tina',
];

$id = (int) ($_GET['id'] ?? 0);
$habitacion = [
    'room_number' => '',
    'room_type'   => 'Doble',
    'room_price'  => '',
    'capacity'    => 2,
    'is_available'=> 1,
    'description' => '',
    'image'       => 'hab1.jpg',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $encontrada = $stmt->fetch();

    if (!$encontrada) {
        mensaje('error', 'Esa habitación no existe.');
        redirigir('habitaciones.php');
    }
    $habitacion = $encontrada;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $habitacion['room_number']  = (int) ($_POST['numero'] ?? 0);
    $habitacion['room_type']    = $_POST['tipo'] ?? '';
    $habitacion['room_price']   = (float) ($_POST['precio'] ?? 0);
    $habitacion['capacity']     = (int) ($_POST['capacidad'] ?? 0);
    $habitacion['is_available'] = isset($_POST['disponible']) ? 1 : 0;
    $habitacion['description']  = trim($_POST['descripcion'] ?? '');
    $habitacion['image']        = $_POST['imagen'] ?? 'hab1.jpg';

    if ($habitacion['room_number'] <= 0) {
        $error = 'Escribe un número de habitación válido.';
    } elseif (!in_array($habitacion['room_type'], $tipos, true)) {
        $error = 'Elige un tipo de habitación de la lista.';
    } elseif ($habitacion['room_price'] <= 0) {
        $error = 'El precio por noche tiene que ser mayor que cero.';
    } elseif ($habitacion['capacity'] < 1 || $habitacion['capacity'] > 6) {
        $error = 'La capacidad debe estar entre 1 y 6 personas.';
    } elseif (!array_key_exists($habitacion['image'], $imagenes)) {
        $error = 'Elige una de las fotografías disponibles.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE room_number = :numero AND id <> :id');
        $stmt->execute(['numero' => $habitacion['room_number'], 'id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'Ya existe otra habitación con ese número.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE rooms
                 SET room_number = :numero, room_type = :tipo, room_price = :precio,
                     capacity = :capacidad, is_available = :disponible,
                     description = :descripcion, image = :imagen
                 WHERE id = :id'
            );
            $stmt->execute([
                'numero'      => $habitacion['room_number'],
                'tipo'        => $habitacion['room_type'],
                'precio'      => $habitacion['room_price'],
                'capacidad'   => $habitacion['capacity'],
                'disponible'  => $habitacion['is_available'],
                'descripcion' => $habitacion['description'] !== '' ? $habitacion['description'] : null,
                'imagen'      => $habitacion['image'],
                'id'          => $id,
            ]);
            mensaje('ok', 'Habitación ' . $habitacion['room_number'] . ' actualizada.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO rooms (room_number, room_type, room_price, capacity, is_available, description, image)
                 VALUES (:numero, :tipo, :precio, :capacidad, :disponible, :descripcion, :imagen)'
            );
            $stmt->execute([
                'numero'      => $habitacion['room_number'],
                'tipo'        => $habitacion['room_type'],
                'precio'      => $habitacion['room_price'],
                'capacidad'   => $habitacion['capacity'],
                'disponible'  => $habitacion['is_available'],
                'descripcion' => $habitacion['description'] !== '' ? $habitacion['description'] : null,
                'imagen'      => $habitacion['image'],
            ]);
            mensaje('ok', 'Habitación ' . $habitacion['room_number'] . ' creada.');
        }

        redirigir('habitaciones.php');
    }
}

$titulo = $id > 0 ? 'Editar habitación' : 'Nueva habitación';
$activo = 'habitaciones';
include __DIR__ . '/includes/header.php';
?>

<div class="pagina-cabeza">
    <div>
        <p class="pagina-intro">Los cambios de precio y disponibilidad se reflejan en el sitio y en el buscador de fechas.</p>
    </div>
    <div class="pagina-acciones">
        <a href="habitaciones.php" class="btn-panel btn-panel-linea">&larr; Volver al listado</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="rejilla-formulario">
    <form method="post" class="bloque formulario-panel" novalidate>
        <?= csrf_campo() ?>

        <div class="campo-doble">
            <div class="campo">
                <label for="numero">Número de habitación</label>
                <input type="number" id="numero" name="numero" value="<?= e((string) $habitacion['room_number']) ?>" min="1" max="999" required>
                <p class="ayuda">Debe ser único en todo el hotel.</p>
            </div>

            <div class="campo">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required>
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= e($tipo) ?>" <?= $habitacion['room_type'] === $tipo ? 'selected' : '' ?>><?= e($tipo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="campo-doble">
            <div class="campo">
                <label for="precio">Precio por noche (USD)</label>
                <input type="number" id="precio" name="precio" value="<?= e((string) $habitacion['room_price']) ?>" min="1" step="0.01" required>
            </div>

            <div class="campo">
                <label for="capacidad">Capacidad</label>
                <input type="number" id="capacidad" name="capacidad" value="<?= (int) $habitacion['capacity'] ?>" min="1" max="6" required>
            </div>
        </div>

        <div class="campo">
            <label for="descripcion">Descripción breve</label>
            <textarea id="descripcion" name="descripcion" rows="3" maxlength="255" placeholder="Ej.: Cama king, balcón lateral y ducha de lluvia."><?= e($habitacion['description']) ?></textarea>
            <p class="ayuda">Se muestra en el buscador de disponibilidad. Máximo 255 caracteres.</p>
        </div>

        <div class="campo">
            <label for="imagen">Fotografía</label>
            <select id="imagen" name="imagen">
                <?php foreach ($imagenes as $archivo => $nombre): ?>
                    <option value="<?= e($archivo) ?>" <?= $habitacion['image'] === $archivo ? 'selected' : '' ?>><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <label class="check">
            <input type="checkbox" name="disponible" value="1" <?= (int) $habitacion['is_available'] === 1 ? 'checked' : '' ?>>
            <span>Disponible para reservar</span>
        </label>

        <div class="formulario-pie">
            <button type="submit" class="btn-panel btn-panel-acento"><?= $id > 0 ? 'Guardar cambios' : 'Crear habitación' ?></button>
            <a href="habitaciones.php" class="btn-panel btn-panel-linea">Cancelar</a>
        </div>
    </form>

    <aside class="bloque nota-lateral">
        <p class="sobrelinea">Antes de guardar</p>
        <h2>Precios y estado</h2>
        <p>El precio se guarda sin impuestos. En el sitio se muestra como “por noche” e incluye el desayuno.</p>
        <p>Si marcas la habitación como no disponible, deja de aparecer en el buscador aunque no tenga reservas. Úsalo para mantenimiento.</p>
        <p>No se puede eliminar una habitación con reservas registradas: primero hay que resolver esas reservas.</p>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
