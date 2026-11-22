<dialog class="grupo-dialogo" id="grupoDialogo" aria-labelledby="grupoTitulo" aria-describedby="grupoNota" aria-modal="true">
    <div class="grupo-caja">
        <button type="button" class="grupo-cerrar" data-grupo-cerrar aria-label="Cerrar la ventana del grupo">
            <?= icono('equis') ?>
        </button>

        <p class="sobrelinea">Programación Web Avanzada</p>
        <h2 id="grupoTitulo">Grupo 04</h2>
        <p class="grupo-subtitulo">Práctica 6 · Sistema de reservas Hotel Mangle</p>

        <ul class="grupo-lista">
            <li><span aria-hidden="true">01</span>Norka Mideros</li>
            <li><span aria-hidden="true">02</span>Jorge Villamar</li>
            <li><span aria-hidden="true">03</span>Liseth Criollo</li>
            <li><span aria-hidden="true">04</span>Bruce Balarezo</li>
            <li><span aria-hidden="true">05</span>Chinlle Carrillo</li>
        </ul>

        <p class="grupo-nota" id="grupoNota">Clase práctica 6. No representa un hotel real.</p>

        <div class="grupo-pie">
            <button type="button" class="btn-panel" data-grupo-cerrar>Cerrar</button>
        </div>
    </div>
</dialog>
