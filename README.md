# Hotel Mangle · Práctica 6

Sistema de reservas de un hotel pequeño en Ayampe, Manabí. Práctica 6 de
**Programación Web Avanzada**, Grupo 04.

## Enlaces

- Sitio en vivo: `https://pwapractica6.infinityfreeapp.com`
- Repositorio: `https://github.com/jorgevillamar369-glitch/PWPractica6`
- Local: `http://localhost:8090`

## Equipo

Grupo 04: Bruce Balarezo, Norka Mideros, Mauro Chinlle, Jorge Villamar y
Liseth Criollo.

## Cuentas de prueba

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@hotelmangle.ec` | `Admin123!` |
| Gerente | `gerente@hotelmangle.ec` | `Gerente123!` |
| Recepción | `recepcion@hotelmangle.ec` | `Recepcion123!` |
| Cliente | `cliente@hotelmangle.ec` | `Cliente123!` |
| Proveedor | `proveedor@hotelmangle.ec` | `Proveedor123!` |

Las contraseñas se guardan con `password_hash()`. Cámbialas antes de un
despliegue real.

## Roles

| Rol | Puede |
| --- | --- |
| Administrador | Gestionar usuarios y supervisar el sistema |
| Gerente | Habitaciones, precios y proveeduría |
| Recepción | Reservas y datos de clientes |
| Cliente | Buscar, reservar y ver sus reservas |
| Proveedor | Ver necesidades y enviar ofertas |

Los permisos se comprueban en el servidor, no solo en el menú.

## Funciones

- Portada, habitaciones y buscador de disponibilidad por fechas.
- Reservas con control de cruces, noches y total.
- Confirmación, cancelación y cierre de estancias.
- Gestión de habitaciones, clientes y usuarios.
- Proveeduría: necesidad, oferta y pedido recibido.
- PDO, CSRF, contraseñas cifradas y sesiones seguras.

## Tecnologías

PHP 8.1 o superior, MySQL, Bootstrap 5, CSS y JavaScript propios. Sin framework
de backend: cada página incluye la conexión y las funciones comunes.

## Instalación local

1. Copia el proyecto a Laragon o XAMPP.
2. Importa `sql/pwapractica6.sql`.
3. Copia `includes/config.example.php` como `includes/config.php` y ajusta los
   datos de MySQL.
4. Abre `http://localhost:8090` o el virtual host.

Con el servidor integrado:

```bash
php -S localhost:8090 -t .
```

## Publicación

- Sube el contenido a `pwapractica6.infinityfreeapp.com/htdocs`, **no** a
  `/htdocs`: allí está la práctica 5.
- Importa `sql/pwapractica6_hosting.sql` en la base del hosting.
- Configura `includes/config.php` con host, nombre, usuario y contraseña de esa
  base.
- `includes/config.php` no se sube al repositorio porque contiene las claves.

El sitio ya está publicado en la URL de arriba.

## Estructura

```
index.php, disponibilidad.php, login.php, registro.php, panel.php
reservas.php, habitaciones.php, clientes.php, usuarios.php, proveeduria.php
includes/   conexión, funciones, sesión y plantillas
assets/     CSS, JS, fuentes, imágenes y Bootstrap local
sql/        base local y base para hosting
```

## Créditos

Fotografías: Pexels. Tipografías: Fraunces e Instrument Sans. Bootstrap 5.3.3.
Clase práctica 6, Grupo 04.
