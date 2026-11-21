# Hotel Mangle · Sistema de reservas

Práctica 6 de **Programación Web Avanzada** (Grupo 04). Aplicación web para
gestionar las reservas de un hotel pequeño en Ayampe, Manabí.

Usa **HTML5, Bootstrap 5, CSS propio, JavaScript sin librerías y PHP con MySQL**.
No hay framework de backend: cada página incluye la conexión, las funciones
comunes y el control de sesión.

## Direcciones

- Sitio local: `http://localhost:8090`
- Repositorio: `https://github.com/jorgevillamar369-glitch/pwapractica6` (crear al publicar)
- Hospedaje: pendiente de subir al panel de InfinityFree

## Cuentas de prueba

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@hotelmangle.ec` | `Admin123!` |
| Gerente del hotel | `gerente@hotelmangle.ec` | `Gerente123!` |
| Recepcionista | `recepcion@hotelmangle.ec` | `Recepcion123!` |
| Cliente | `cliente@hotelmangle.ec` | `Cliente123!` |
| Proveedor | `proveedor@hotelmangle.ec` | `Proveedor123!` |

Las contraseñas están cifradas con `password_hash()` y se verifican con
`password_verify()`. Cámbialas antes de publicar el sitio.

## Roles y permisos

| Módulo | Administrador | Gerente | Recepción | Cliente | Proveedor |
| --- | :---: | :---: | :---: | :---: | :---: |
| Panel | Sí | Sí | Sí | Sí | Sí |
| Habitaciones | Ver | Crear, editar, eliminar | Ver | Consultar y reservar | No |
| Reservas | Todas | Todas | Todas | Solo las propias | No |
| Clientes | Sí | No | Sí | No | No |
| Usuarios | Sí | No | No | No | No |
| Proveeduría | Sí | Sí | No | No | Sí |

Los permisos se comprueban en el servidor con `exigir_roles()`, no solo en el
menú. Un usuario que escriba una URL que no le corresponde recibe un 403.

## Qué hace el sistema

- **Portada pública** con habitaciones, tarifas, servicios y buscador de fechas.
- **Búsqueda de disponibilidad** por rango de fechas y número de huéspedes. Una
  habitación se considera ocupada si tiene una reserva pendiente o confirmada
  que se cruce con las fechas pedidas.
- **Reserva en línea** para clientes registrados, con cálculo de noches y total.
  La reserva queda pendiente hasta que recepción la confirma.
- **Recepción** crea reservas a nombre de un cliente y las confirma en el acto.
- **Gerencia** administra habitaciones, precios, capacidad y disponibilidad.
- **Administración** gestiona las cuentas de los cinco roles.
- **Proveeduría**: gerencia publica necesidades de insumos; los proveedores
  envían precio por unidad; gerencia acepta una oferta y marca el pedido como
  recibido.
- **Seguridad**: PDO con consultas preparadas, CSRF en todos los formularios,
  sesiones con `HttpOnly` y `SameSite=Lax`, y contraseñas cifradas.

## Datos de ficción del hotel

La portada describe un hotel ficticio. Estos valores son la fuente de verdad de
esa copy; si cambian, se actualizan aquí y en la base cuando corresponda.

| Dato | Valor |
| --- | --- |
| Habitaciones | 8, según la tabla `rooms` |
| Apertura | 2019 |
| Playa | 3 minutos a pie |
| Piscina | 14 metros |
| Parqueadero | 6 puestos |
| Wi-Fi | 100 Mbps |
| Desayuno | 07:00 a 10:00 |
| Llegada | desde las 14:00 |
| Salida | hasta las 11:00 |

## Base de datos

El archivo `sql/pwapractica6.sql` crea la base, las tablas y los datos de
ejemplo. `sql/pwapractica6_hosting.sql` es el mismo contenido sin
`CREATE DATABASE` ni `USE`, para importar en un hospedaje compartido.

Tablas del enunciado:

- `roles`: Administrator, Hotel Manager, Receptionist, Customer, Supplier.
- `users`: nombre, correo único, contraseña cifrada, rol y teléfono.
- `rooms`: número único, tipo, precio, disponibilidad, capacidad y foto.
- `bookings`: cliente, habitación, fechas, huéspedes, total y estado.
- `supplies`: insumos que necesita el hotel y proveedor asignado.

Tablas y campos añadidos para completar el flujo:

- `supply_offers`: ofertas de los proveedores por cada necesidad.
- `bookings.status`: `pending`, `confirmed`, `cancelled`, `completed`.
- `bookings.guests`, `bookings.total` y `bookings.notes`.
- `rooms.capacity`, `rooms.description` y `rooms.image`.
- `users.phone`, `users.active` y `users.created_at`.

## Requisitos

- PHP 8.1 o superior con `pdo_mysql` y `mbstring`.
- MySQL 8 o MariaDB 10.4 o superior.
- Laragon, XAMPP o WAMP.

## Instalación local

1. Copia la carpeta a `C:\laragon\www\pwapractica6`.
2. Importa `sql/pwapractica6.sql` desde phpMyAdmin o por consola.
3. Copia `includes/config.example.php` como `includes/config.php` y ajusta el
   puerto y las credenciales. En este equipo la base corre en el puerto `3307`
   con el usuario `root` sin contraseña.
4. Abre `http://localhost:8090` o configura un virtual host.

Con el servidor integrado de PHP:

```bash
php -S localhost:8090 -t .
```

## Publicar en el hosting

Estos pasos están pensados para InfinityFree, el hospedaje gratuito usado en la
práctica anterior.

1. Entra al panel de InfinityFree y abre **Create Account**.
2. Elige un subdominio, por ejemplo `hotelmangle.infinityfreeapp.com`. Espera a
   que el panel termine de crearlo.
3. En **MySQL Databases**, crea una base. Anota el nombre completo con prefijo,
   el usuario, la contraseña y el host (normalmente `sqlXXX.infinityfree.com`).
4. Entra a phpMyAdmin desde el panel, selecciona esa base e importa
   `sql/pwapractica6_hosting.sql`.
5. Abre `includes/config.php` y cambia solo estas constantes:

   ```php
   const APP_DEBUG  = false;
   const DB_HOST    = 'sqlXXX.infinityfree.com';
   const DB_PORT    = '3306';
   const DB_NOMBRE  = 'if0_XXXXXXXX_hotelmangle';
   const DB_USUARIO = 'if0_XXXXXXXX';
   const DB_CLAVE   = 'la_clave_del_panel';
   ```

6. Sube el **contenido** de la carpeta a `htdocs`, no la carpeta contenedora.
   Deben verse `index.php`, `includes/`, `assets/` y `sql/`.
7. Abre `https://hotelmangle.infinityfreeapp.com` y prueba las cinco cuentas.
8. Cambia las contraseñas de demostración desde el panel de administración.

El archivo `includes/.htaccess` bloquea el acceso directo al código interno en
servidores Apache. El archivo `.htaccess` de la raíz evita listar directorios.

## Subir a GitHub

```bash
git init
git add .
git commit -m "Sistema de reservas Hotel Mangle"
git branch -M main
git remote add origin https://github.com/jorgevillamar369-glitch/pwapractica6.git
git push -u origin main
```

`includes/config.php` está en `.gitignore` porque contiene las contraseñas. En el
repositorio queda `config.example.php` como plantilla.

## Estructura

```
pwapractica6/
├── index.php                 Portada pública
├── disponibilidad.php        Buscador de fechas
├── login.php, registro.php   Acceso y registro de clientes
├── panel.php                 Panel según el rol
├── habitaciones.php          Listado y administración
├── habitacion_form.php       Alta y edición de habitaciones
├── habitacion_eliminar.php   Baja con control de reservas
├── reservas.php              Reservas propias o de todo el hotel
├── reserva_form.php          Nueva reserva con control de cruces
├── reserva_estado.php        Confirmar, cancelar o finalizar
├── clientes.php              Directorio de huéspedes (recepción)
├── cliente_form.php          Alta y edición de clientes
├── usuarios.php              Cuentas del sistema (administración)
├── usuario_form.php          Alta y edición de usuarios
├── usuario_eliminar.php      Baja con control de historial
├── proveeduria.php           Necesidades y ofertas
├── proveeduria_detalle.php   Comparación de ofertas
├── proveeduria_accion.php    Aceptar, rechazar o recibir
├── necesidad_form.php        Publicar una necesidad
├── oferta_form.php           Enviar una oferta (proveedor)
├── includes/                 Conexión, funciones, sesión y plantillas
├── assets/                   CSS, JS, fuentes, imágenes y Bootstrap local
├── sql/                      Base de datos local y para hosting
└── README.md
```

## Créditos

- Fotografías: Pexels (uso libre, sin atribución obligatoria).
- Tipografías: Fraunces e Instrument Sans (SIL Open Font License), locales.
- Bootstrap 5.3.3 (MIT).

Proyecto académico. No representa un hotel real.
