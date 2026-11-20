<?php
require_once __DIR__ . '/includes/auth.php';

$titulo = APP_NOMBRE . ' · Hotel frente al mar en Ayampe';
$pagina = 'inicio';

$habitaciones = $pdo->query(
    'SELECT room_type, MIN(room_price) AS precio, MAX(capacity) AS capacidad, MIN(image) AS imagen, COUNT(*) AS total
     FROM rooms
     WHERE is_available = 1
     GROUP BY room_type
     ORDER BY precio'
)->fetchAll();

$totalHabitaciones = (int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
$textoHabitaciones = $totalHabitaciones === 1
    ? 'Una habitación'
    : ucfirst(numero_en_letras($totalHabitaciones)) . ' habitaciones';

$formas = count($habitaciones);
$textoFormas = $formas === 1
    ? 'Una forma de quedarse'
    : ucfirst(numero_en_letras($formas)) . ' formas de quedarse';

$descripciones = [
    'Doble'    => 'Cama king o dos individuales, balcón y ducha de lluvia. Para dos personas.',
    'Suite'    => 'Sala pequeña, tina y vista al jardín de mangle. Terraza en la planta alta.',
    'Familiar' => 'Dos ambientes con litera, mesa para cuatro y ventanales hacia la piscina.',
];

$llegada = date('Y-m-d', strtotime('+7 days'));
$salida  = date('Y-m-d', strtotime('+9 days'));

include __DIR__ . '/includes/sitio_header.php';
?>

<main>
    <section class="portada">
        <div class="portada-fondo" style="background-image:url('assets/img/hab3.jpg')"></div>
        <div class="portada-velo"></div>

        <div class="contenedor portada-contenido">
            <p class="sobrelinea claro">Ayampe · Manabí · Ecuador</p>
            <h1><?= e($textoHabitaciones) ?><br>entre el manglar<br>y el mar.</h1>
            <p class="portada-texto">Un hotel pequeño en la costa de Manabí. Desayuno, piscina y la playa a tres minutos a pie.</p>
            <p class="portada-datos"><?= e($textoHabitaciones) ?> <span>·</span> Piscina <span>·</span> Desayuno incluido</p>
        </div>

        <div class="contenedor">
            <form class="buscador" action="disponibilidad.php" method="get">
                <div class="buscador-campo">
                    <label for="desde">Llegada</label>
                    <input type="date" id="desde" name="desde" value="<?= e($llegada) ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="buscador-campo">
                    <label for="hasta">Salida</label>
                    <input type="date" id="hasta" name="hasta" value="<?= e($salida) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                <div class="buscador-campo">
                    <label for="personas">Huéspedes</label>
                    <select id="personas" name="personas">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?> <?= $i === 1 ? 'persona' : 'personas' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn-sitio btn-sitio-acento btn-buscar">Ver disponibilidad</button>
            </form>
        </div>
    </section>

    <section class="seccion" id="habitaciones">
        <div class="contenedor">
            <div class="seccion-cabeza">
                <div>
                    <p class="sobrelinea">01 · Habitaciones</p>
                    <h2><?= e($textoFormas) ?></h2>
                </div>
                <p class="seccion-intro">Todas incluyen desayuno y acceso a la piscina. Precios por habitación y noche.</p>
            </div>

            <div class="rejilla-habitaciones">
                <?php $n = 0; foreach ($habitaciones as $h): $n++; ?>
                    <article class="tarjeta-habitacion">
                        <div class="tarjeta-foto">
                            <img src="<?= e(foto_habitacion($h['imagen'])) ?>" alt="Habitación <?= e($h['room_type']) ?> del Hotel Mangle" loading="lazy">
                            <span class="tarjeta-numero"><?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="tarjeta-cuerpo">
                            <div class="tarjeta-fila">
                                <h3><?= e($h['room_type']) ?></h3>
                                <p class="precio"><?= dinero($h['precio']) ?><small>/ noche</small></p>
                            </div>
                            <p class="tarjeta-texto"><?= e($descripciones[$h['room_type']] ?? 'Habitación exterior con baño privado.') ?></p>
                            <p class="tarjeta-meta">Hasta <?= (int) $h['capacidad'] ?> personas · <?= (int) $h['total'] ?> disponibles</p>
                            <a class="enlace-flecha" href="disponibilidad.php?personas=<?= (int) $h['capacidad'] ?>&amp;desde=<?= e($llegada) ?>&amp;hasta=<?= e($salida) ?>">
                                Ver fechas disponibles <?= icono('flecha') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="seccion seccion-hotel" id="hotel">
        <div class="contenedor rejilla-editorial">
            <figure class="editorial-foto">
                <img src="assets/img/hero.jpg" alt="Piscina del hotel entre palmeras y camastros" loading="lazy">
                <figcaption>La piscina, entre palmeras y camastros. 14 metros y poca gente.</figcaption>
            </figure>

            <div class="editorial-texto">
                <p class="sobrelinea">02 · El hotel</p>
                <h2>Una casa de playa,<br>con pocas habitaciones.</h2>
                <p>Abrimos en 2019 con una idea sencilla: que el huésped se sienta en casa de un amigo frente al mar. Sin música en los pasillos ni animación en la piscina.</p>
                <p>Madera y caña, techos altos y ventilación cruzada. El agua caliente es solar y el jardín se riega con lluvia recogida.</p>

                <dl class="cifras">
                    <div><dt><?= $totalHabitaciones ?></dt><dd>habitaciones</dd></div>
                    <div><dt>3 min</dt><dd>a la playa</dd></div>
                    <div><dt>2019</dt><dd>año de apertura</dd></div>
                </dl>
            </div>
        </div>
    </section>

    <section class="seccion seccion-servicios" id="servicios">
        <div class="contenedor">
            <div class="seccion-cabeza">
                <div>
                    <p class="sobrelinea">03 · Servicios</p>
                    <h2>Lo que está incluido</h2>
                </div>
                <p class="seccion-intro">Sin cargos sorpresa. Si algo no aparece en esta lista, se cobra aparte y se avisa antes.</p>
            </div>

            <div class="lista-servicios">
                <div class="servicio"><span class="servicio-num">01</span><div><h3>Desayuno de 07:00 a 10:00</h3><p>Fruta de temporada, pan de masa madre, huevos al gusto y café de Loja.</p></div></div>
                <div class="servicio"><span class="servicio-num">02</span><div><h3>Piscina y jardín</h3><p>Abierta de 08:00 a 20:00. Camastros, toallas de piscina y ducha exterior.</p></div></div>
                <div class="servicio"><span class="servicio-num">03</span><div><h3>Wi-Fi en todo el hotel</h3><p>Fibra de 100 Mbps. También llega a la terraza y a la zona de hamacas.</p></div></div>
                <div class="servicio"><span class="servicio-num">04</span><div><h3>Parqueadero privado</h3><p>Seis puestos dentro del terreno, sin reserva previa y sin costo.</p></div></div>
                <div class="servicio"><span class="servicio-num">05</span><div><h3>Guarda tablas y duchas</h3><p>Espacio para tablas de surf, bidones de agua dulce y tendedero.</p></div></div>
                <div class="servicio"><span class="servicio-num">06</span><div><h3>Recorridos por el manglar</h3><p>Salidas a las 07:30 con guía local. Se coordinan en recepción el día anterior.</p></div></div>
            </div>
        </div>
    </section>

    <section class="seccion seccion-entorno" id="entorno">
        <div class="entorno-foto">
            <img src="assets/img/playa.jpg" alt="Playa de arena blanca y agua turquesa" loading="lazy">
            <p class="entorno-pie">La playa, a tres minutos a pie.</p>
        </div>

        <div class="contenedor rejilla-entorno">
            <div>
                <p class="sobrelinea">04 · El entorno</p>
                <h2>El pueblo queda<br>a un lado.</h2>
            </div>
            <div class="entorno-texto">
                <p>Ayampe está en el límite entre Manabí y Santa Elena, donde la montaña llega hasta el mar. El hotel queda en la vía a los Mangles, lejos del ruido de la carretera y cerca de la desembocadura del río.</p>
                <p>Desde Guayaquil son tres horas y media en auto. Desde Puerto López, veinte minutos. La cooperativa de buses deja en la entrada del pueblo, a diez minutos caminando.</p>
                <a class="enlace-flecha" href="disponibilidad.php">Consultar disponibilidad <?= icono('flecha') ?></a>
            </div>
        </div>
    </section>

    <section class="franja-reserva">
        <div class="contenedor franja-interior">
            <div>
                <p class="sobrelinea claro">Reserva directa</p>
                <h2>Sin comisiones<br>ni intermediarios.</h2>
            </div>
            <div class="franja-acciones">
                <p>Escríbenos a <a href="mailto:reservas@hotelmangle.ec">reservas@hotelmangle.ec</a> o llama al <a href="tel:+593980000000">098 000 0000</a>. También puedes reservar en línea y pagar al llegar.</p>
                <a href="registro.php" class="btn-sitio btn-sitio-acento">Crear cuenta y reservar</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/sitio_footer.php'; ?>
