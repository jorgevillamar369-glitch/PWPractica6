<?php
/**
 * Funciones compartidas: permisos, formatos, seguridad y vistas pequeñas.
 */

declare(strict_types=1);

const ROL_ADMIN       = 'Administrator';
const ROL_GERENTE     = 'Hotel Manager';
const ROL_RECEPCION   = 'Receptionist';
const ROL_CLIENTE     = 'Customer';
const ROL_PROVEEDOR   = 'Supplier';

/* ------------------------------------------------------------
   Sesión y permisos
   ------------------------------------------------------------ */

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function usuario_id(): int
{
    return (int) ($_SESSION['usuario']['id'] ?? 0);
}

function rol_actual(): string
{
    return $_SESSION['usuario']['rol'] ?? '';
}

function exigir_login(): void
{
    if (!usuario_actual()) {
        mensaje('aviso', 'Inicia sesión para continuar.');
        redirigir('login.php');
    }
}

/**
 * Detiene la página si el rol no está permitido.
 */
function exigir_roles(array $roles): void
{
    exigir_login();
    if (!in_array(rol_actual(), $roles, true)) {
        http_response_code(403);
        mensaje('error', 'Tu rol no tiene permiso para entrar a esa sección.');
        redirigir('panel.php');
    }
}

function es_staff(): bool
{
    return in_array(rol_actual(), [ROL_ADMIN, ROL_GERENTE, ROL_RECEPCION], true);
}

function etiqueta_rol(string $rol): string
{
    return [
        ROL_ADMIN     => 'Administrador',
        ROL_GERENTE   => 'Gerente del hotel',
        ROL_RECEPCION => 'Recepcionista',
        ROL_CLIENTE   => 'Cliente',
        ROL_PROVEEDOR => 'Proveedor',
    ][$rol] ?? $rol;
}

/* ------------------------------------------------------------
   Redirecciones, mensajes y CSRF
   ------------------------------------------------------------ */

function redirigir(string $archivo, array $parametros = []): void
{
    $destino = $archivo;
    if ($parametros) {
        $destino .= '?' . http_build_query($parametros);
    }
    header('Location: ' . $destino);
    exit;
}

/**
 * Guarda un aviso para mostrarlo después de una redirección.
 * Tipos: ok, error, aviso.
 */
function mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $texto];
}

function mostrar_mensaje(): void
{
    if (empty($_SESSION['mensaje'])) {
        return;
    }
    $m = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);

    $clase = ['ok' => 'aviso-ok', 'error' => 'aviso-error', 'aviso' => 'aviso-info'][$m['tipo']] ?? 'aviso-info';
    echo '<div class="aviso ' . $clase . '" role="status">' . e($m['texto']) . '</div>';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    $enviado = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals(csrf_token(), $enviado)) {
        http_response_code(403);
        exit('El formulario expiró. Vuelve atrás y recarga la página.');
    }
}

/* ------------------------------------------------------------
   Formatos
   ------------------------------------------------------------ */

function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function dinero(float|int|string $valor): string
{
    return '$ ' . number_format((float) $valor, 2, ',', '.');
}

function fecha_corta(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $d = date_create($fecha);
    return $d->format('j') . ' ' . $meses[(int) $d->format('n') - 1] . ' ' . $d->format('Y');
}

function fecha_larga(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $dias  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $d = date_create($fecha);
    return $dias[(int) $d->format('w')] . ', ' . $d->format('j') . ' de ' . $meses[(int) $d->format('n') - 1];
}

function noches(string $desde, string $hasta): int
{
    $a = date_create($desde);
    $b = date_create($hasta);
    return max(0, (int) $a->diff($b)->days);
}

function estado_reserva(string $estado): array
{
    return [
        'pending'   => ['Pendiente', 'warn'],
        'confirmed' => ['Confirmada', 'ok'],
        'cancelled' => ['Cancelada', 'off'],
        'completed' => ['Finalizada', 'neutral'],
    ][$estado] ?? [$estado, 'neutral'];
}

function estado_provision(string $estado): array
{
    return [
        'requested' => ['Solicitada', 'warn'],
        'offered'   => ['Con oferta', 'info'],
        'received'  => ['Recibida', 'ok'],
    ][$estado] ?? [$estado, 'neutral'];
}

function estado_oferta(string $estado): array
{
    return [
        'pending'  => ['En revisión', 'warn'],
        'accepted' => ['Aceptada', 'ok'],
        'rejected' => ['Rechazada', 'off'],
    ][$estado] ?? [$estado, 'neutral'];
}

function foto_habitacion(?string $archivo): string
{
    $permitidas = ['hab1.jpg', 'hab2.jpg', 'hab3.jpg', 'sala.jpg'];
    if ($archivo && in_array($archivo, $permitidas, true)) {
        return 'assets/img/' . $archivo;
    }
    return 'assets/img/hab1.jpg';
}

/* ------------------------------------------------------------
   Consultas de apoyo
   ------------------------------------------------------------ */

/**
 * Comprueba si una habitación está libre en un rango de fechas.
 * Las reservas pendientes también bloquean la habitación.
 */
function habitacion_libre(PDO $pdo, int $habitacion, string $desde, string $hasta, ?int $ignorar = null): bool
{
    $sql = "SELECT COUNT(*) FROM bookings
            WHERE room_id = :habitacion
              AND status IN ('pending', 'confirmed')
              AND check_in_date < :hasta
              AND check_out_date > :desde";
    $params = ['habitacion' => $habitacion, 'desde' => $desde, 'hasta' => $hasta];

    if ($ignorar !== null) {
        $sql .= ' AND id <> :ignorar';
        $params['ignorar'] = $ignorar;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() === 0;
}

function fecha_es_valida(?string $fecha): bool
{
    if (!$fecha) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

/* ------------------------------------------------------------
   Iconos (SVG en línea, sin librerías)
   ------------------------------------------------------------ */

function icono(string $nombre, string $clase = ''): string
{
    $trazos = [
        'panel'    => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'cama'     => '<path d="M3 18V8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10"/><path d="M3 14h18"/><path d="M3 18v2M21 18v2"/><path d="M7 10h3v4H7z"/>',
        'calendario' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'usuarios' => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.3"/><path d="M17 14c2.5 0 4.5 2 4.5 4.5"/>',
        'caja'     => '<path d="M21 8v9a2 2 0 0 1-1.2 1.8l-7 3a2 2 0 0 1-1.6 0l-7-3A2 2 0 0 1 3 17V8"/><path d="M3 8l8-3.5a2 2 0 0 1 1.8 0L21 8l-8 3.5a2 2 0 0 1-1.8 0z"/><path d="M12 11.5V21"/>',
        'salir'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'mas'      => '<path d="M12 5v14M5 12h14"/>',
        'lapiz'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'basura'   => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 14h10l1-14"/><path d="M10 11v5M14 11v5"/>',
        'check'    => '<path d="M20 6L9 17l-5-5"/>',
        'equis'    => '<path d="M18 6L6 18M6 6l12 12"/>',
        'buscar'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'flecha'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'reloj'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    $path = $trazos[$nombre] ?? '';
    return '<svg class="icono ' . $clase . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
