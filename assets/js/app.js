/**
 * Hotel Mangle · Interacciones del sitio
 * JavaScript sin librerías, salvo Bootstrap para el menú del panel.
 */

(function () {
    'use strict';

    /* Menú del sitio público */
    var botonMenu = document.querySelector('[data-menu-sitio]');
    var menuSitio = document.getElementById('menuSitio');

    if (botonMenu && menuSitio) {
        botonMenu.addEventListener('click', function () {
            var abierto = menuSitio.classList.toggle('abierto');
            botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });

        menuSitio.querySelectorAll('a').forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                menuSitio.classList.remove('abierto');
                botonMenu.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /* Cerrar el menú lateral al navegar en móvil */
    var menuLateral = document.getElementById('menuLateral');
    if (menuLateral && window.bootstrap) {
        menuLateral.querySelectorAll('a').forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    var instancia = bootstrap.Offcanvas.getInstance(menuLateral);
                    if (instancia) {
                        instancia.hide();
                    }
                }
            });
        });
    }

    /* Confirmación antes de eliminar o cancelar */
    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            var texto = form.getAttribute('data-confirmar');
            if (texto && !window.confirm(texto)) {
                evento.preventDefault();
            }
        });
    });

    /* Filtro rápido de tablas */
    document.querySelectorAll('[data-filtro-tabla]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            var tabla = document.getElementById(campo.getAttribute('data-filtro-tabla'));
            if (!tabla) {
                return;
            }
            var termino = normalizar(campo.value);
            tabla.querySelectorAll('tbody tr').forEach(function (fila) {
                var visible = normalizar(fila.textContent).indexOf(termino) !== -1;
                fila.style.display = visible ? '' : 'none';
            });
        });
    });

    function normalizar(texto) {
        return String(texto)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    /* Fechas: la salida siempre va después de la llegada */
    var desde = document.getElementById('desde');
    var hasta = document.getElementById('hasta');

    if (desde && hasta) {
        desde.addEventListener('change', function () {
            if (!desde.value) {
                return;
            }
            var siguiente = sumarDias(desde.value, 1);
            hasta.min = siguiente;
            if (!hasta.value || hasta.value <= desde.value) {
                hasta.value = sumarDias(desde.value, 2);
            }
            actualizarResumen();
        });
        hasta.addEventListener('change', actualizarResumen);
    }

    function sumarDias(fecha, dias) {
        var partes = fecha.split('-');
        var d = new Date(Date.UTC(+partes[0], +partes[1] - 1, +partes[2]));
        d.setUTCDate(d.getUTCDate() + dias);
        return d.toISOString().slice(0, 10);
    }

    function diasEntre(a, b) {
        var pa = a.split('-');
        var pb = b.split('-');
        var da = Date.UTC(+pa[0], +pa[1] - 1, +pa[2]);
        var db = Date.UTC(+pb[0], +pb[1] - 1, +pb[2]);
        return Math.round((db - da) / 86400000);
    }

    function formatearDinero(valor) {
        return '$ ' + valor.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /* Resumen de la reserva */
    var formularioReserva = document.querySelector('[data-reserva]');
    if (formularioReserva && desde && hasta) {
        actualizarResumen();
    }

    function actualizarResumen() {
        var formulario = document.querySelector('[data-reserva]');
        if (!formulario || !desde.value || !hasta.value) {
            return;
        }

        var precio = parseFloat(formulario.getAttribute('data-precio')) || 0;
        var noches = diasEntre(desde.value, hasta.value);
        if (noches <= 0) {
            return;
        }

        var nochesTexto = document.getElementById('resumenNoches');
        var totalTexto = document.getElementById('resumenTotal');
        if (nochesTexto) {
            nochesTexto.textContent = noches;
        }
        if (totalTexto) {
            totalTexto.textContent = formatearDinero(precio * noches);
        }
    }

    /* Diálogo accesible del grupo */
    var grupoDialogo = document.getElementById('grupoDialogo');
    if (grupoDialogo && typeof grupoDialogo.showModal === 'function') {
        var grupoFoco = null;

        document.querySelectorAll('[data-grupo]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                if (menuSitio) {
                    menuSitio.classList.remove('abierto');
                }
                if (botonMenu) {
                    botonMenu.setAttribute('aria-expanded', 'false');
                }
                if (!grupoDialogo.open) {
                    grupoFoco = document.activeElement;
                    grupoDialogo.showModal();
                    document.body.classList.add('sin-scroll');
                }
            });
        });

        document.querySelectorAll('[data-grupo-cerrar]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                grupoDialogo.close();
            });
        });

        grupoDialogo.addEventListener('click', function (evento) {
            if (evento.target === grupoDialogo) {
                grupoDialogo.close();
            }
        });

        grupoDialogo.addEventListener('close', function () {
            document.body.classList.remove('sin-scroll');
            if (grupoFoco) {
                grupoFoco.focus();
                grupoFoco = null;
            }
        });
    }
})();
