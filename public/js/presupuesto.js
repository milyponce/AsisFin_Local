$(document).ready(iniciar);


function iniciar() {
    verificarSesion().then(sesionActiva => {
        if (!sesionActiva) {
            mostrarAdvertencia();
        }
    });

    $("#btn_agregar_presupuesto").click(agregar);
    $(document).on("click", ".eliminar", eliminar);
    $(document).on("click", ".modificar", MostrarModificacion);

    categorias();
    mostrar();

    grafico();
    $('input[name="grafico"]').on('change', grafico);

    verificarAlertas();
}

function categorias() {
    $("#idCategoria").empty();
    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "presupuesto",
            accion: "categorias",
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            $("<option/>").val("0").text("Seleccione una Categoria").appendTo('#idCategoria');
            respuesta.forEach(function (categoria) {
                $("<option/>").val(categoria.id).text(categoria.nombre).appendTo('#idCategoria');
            });
        }
    });
}


function verificarAlertas() {
    $.post("../public/php/rutasPagPHP.php", {
        pag: "presupuesto",
        accion: "verificar_alertas",
        nocache: Math.random()
    }, function (res) {
        if (!res.hay_alertas || !Array.isArray(res.alertas) || res.alertas.length === 0) return;

        // Crear contenedor si no existe
        let $container = $("#alertas-container");
        if (!$container.length) {
            $("body").prepend('<div id="alertas-container" class="p-3" style="position:fixed;top:30%;left:50%;transform:translate(-50%,-50%);z-index:1050;width:90%;max-width:500px;"></div>');
            $container = $("#alertas-container");
        }
        $container.empty();
        $("body").append('<div id="alertas-backdrop"></div>');

        // Crear estructura de alerta
        const $toast = $(`
            <div class="toast-container">
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header" style="background: var(--gradient-primary); color: var(--light-color);">
                        <strong class="me-auto"><i class="bi bi-exclamation-triangle-fill me-2"></i>Alertas de Presupuesto</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body p-0" style="background-color: var(--light-color);">
                        <div class="list-group list-group-flush" id="alertas-list"></div>
                    </div>
                </div>
            </div>
        `);
        $container.append($toast);

        const $lista = $("#alertas-list");

        // Función para crear una alerta
        const crearAlerta = (a) => {
            let color = "#4895ef"; // default
            if (a.porcentaje >= 90) color = "#0109f9";
            else if (a.porcentaje >= 75) color = "#3f37c9";

            return $(`
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">${a.categoria}</h5>
                        <span class="badge rounded-pill" style="background-color: ${color}; color: white;">${a.porcentaje}%</span>
                    </div>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar" style="width: ${a.porcentaje}%; background: var(--gradient-primary);" aria-valuenow="${a.porcentaje}"></div>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-4"><strong>Presupuesto:</strong> €${a.presupuesto}</div>
                        <div class="col-4"><strong>Gastado:</strong> €${a.gastado}</div>
                        <div class="col-4"><strong>Exceso:</strong> €${a.exceso}</div>
                    </div>
                </div>
            `);
        };

        // Agregar todas las alertas
        res.alertas.forEach(a => $lista.append(crearAlerta(a)));

        // Inicializar toast y cerrar
        if (typeof bootstrap !== 'undefined') {
            document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t, { autohide: false }));
        }

        $(".btn-close").on("click", () => {
            $("#alertas-backdrop, #alertas-container").remove();
        });

    }, "json").fail((_, __, err) => console.error("Error al verificar alertas:", err));
}


function agregar(e) {
    e.preventDefault();
    let idPresupuesto = $("#id_presupuesto").val();
    let idCategoria = $("#idCategoria").val();
    let monto = $("#idmonto").val();

    if (!idPresupuesto) { // Si no hay presupuesto, guarda nuevo
        if (idCategoria == 0) {
            $("#idInfo").text("Seleccione una categoria");
        } else if (!monto) {
            $("#idInfo").text("Ingrese un monto");
        } else {
            $("#idInfo").text("");

            $.ajax({
                type: "POST",
                url: "../public/php/rutasPagPHP.php",
                data: {
                    pag: "presupuesto",
                    accion: "guardar",
                    idCategoria: idCategoria,
                    monto: monto,
                    nocache: Math.random()
                },
                success: function (respuestaGuardar) {
                    $("#idmonto").val(""); // Limpia el monto
                    $("#idCategoria").empty(); // Limpia la categoría seleccionada
                    categorias();
                    mostrar();
                    grafico();
                },
                error: function (xhr, status, error) {
                    console.error("Error al guardar: " + error);
                }
            });
        }

    } else { //actualizar
        $.ajax({
            type: "POST",
            url: "../public/php/rutasPagPHP.php",
            data: {
                pag: "presupuesto",
                accion: "actualizarCategoria",
                idPresupuesto: idPresupuesto,
                idCategoria: idCategoria,
                monto: monto,
                nocache: Math.random()
            },
            success: function (respuesta) {
                $("#idmonto").val(""); // Limpiar el monto
                $("#id_presupuesto").val(""); // Limpiar el ID de presupuesto
                $("#btn_agregar_presupuesto").val("Agregar al presupuesto"); // Resetear el botón
                $("#idInfo").text("Se ha actualizado el presupuesto"); // Mensaje de éxito
                categorias();
                mostrar();
                grafico();
            },
            error: function (xhr, status, error) {
                console.error("Error: " + error); // Manejar errores
            }
        });
    }
}

function eliminar(e) {
    let categoria = $(e.target).data("id_categoria");
    let presupuesto = $(e.target).data("id_presupuesto");

    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "presupuesto",
            accion: "eliminar",
            id_presupuesto: presupuesto,
            id_categoria: categoria,
            nocache: Math.random()
        },
        success: function (respuesta) {
            $("#idInfo").text("Se ha eliminado una categoria el presupuesto");
            categorias();
            mostrar();
            grafico()
        },
        error: function (xhr, status, error) { }
    });

}

function MostrarModificacion(e) {
    let presupuesto = $(e.target).data("id_presupuesto");

    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "presupuesto",
            accion: "motrarModificacion",
            id_mostrarModificar: presupuesto,
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            // Limpiamos el select para evitar datos duplicados
            $("#idCategoria").empty();
            respuesta.forEach(function (c) {
                $("<option/>").val(c.id_categoria).text(c.nombre_categoria).appendTo("#idCategoria");
                $("#idmonto").val(c.monto_presupuesto);
                $("#id_presupuesto").val(c.id_presupuesto);
                $("#btn_agregar_presupuesto").val("Actualizar")
            });
        },
        error: function (xhr, status, error) {
            console.error("Error en AJAX:", error);
        }
    });
}

function mostrar() {
    $("#presupuestoBody").empty();
    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "presupuesto",
            accion: "mostrarTabla",
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            let totalPresu = 0;
            respuesta.forEach(function (t) {
                let tr = $("<tr/>");
                $("<td/>").text(t.nombre_categoria).appendTo(tr);
                $("<td/>").text(t.monto).appendTo(tr);
                let botones = `<button data-id_categoria="${t.id_categoria}" data-id_presupuesto="${t.id_presupuesto}" class="btn btn-primary eliminar"> Eliminar </button>
                             <button data-id_categoria="${t.id_categoria}" data-id_presupuesto="${t.id_presupuesto}" class="btn btn-primary modificar"> Modificar </button>`;
                $("<td/>").html(botones).appendTo(tr);
                $(tr).appendTo("#presupuestoBody");

                // Actualizar el total
                totalPresu += parseFloat(t.monto);
            });
            let trTotal = $("<tr/>");
            $("<td/>").text("Total del presupuesto").appendTo(trTotal);
            $("<td/>").attr("colspan", "2").text(totalPresu).appendTo(trTotal);
            $(trTotal).appendTo("#presupuestoBody");
        },
        error: function (xhr, status, error) {
        }
    });
}


let graficos = null; // Variable global para almacenar el gráfico


function grafico() {
    let canvas = document.getElementById('idGrafico');

    if (!canvas) {
        console.error("Error: No se encontró el elemento <canvas> con id 'idGrafico'");
        return;
    }

    let ctx = canvas.getContext('2d');

    // Si ya existe un gráfico en este canvas, lo eliminamos correctamente
    if (graficos instanceof Chart) {
        graficos.destroy();
    }

    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php", // Modificar esta ruta
        data: {
            pag: "presupuesto",
            accion: "mostrar_datos_graficos",
            nocache: Math.random() // Agregar el parámetro nocache para evitar la caché del navegador
        },
        dataType: "json",
        success: function (respuesta) {

            // Verificamos que la respuesta sea válida
            if (respuesta.length === 0) {
                console.log("Error: La respuesta no contiene datos válidos");
                return;
            }

            $("#mostraGrafico").css("display", "block"); // Mostrar el gráfico

            // Extraemos los valores de los datos
            const nombres = respuesta.map(d => d.nombre_categoria || "Desconocido");
            const montosTotales = respuesta.map(d => parseFloat(d.presupuesto_monto) || 0);
            const montosGastados = respuesta.map(d => parseFloat(d.monto_gastado) || 0);

            // Obtenemos la opción seleccionada del radio button
            let selectedOption = $('input[name="grafico"]:checked').val();

            // Validamos que se haya seleccionado una opción
            if (!selectedOption) {
                console.error("Error: No se ha seleccionado un tipo de gráfico");
                return;
            }


            // Creamos el gráfico con la opción seleccionada
            graficos = new Chart(ctx, {
                type: selectedOption,
                data: {
                    labels: nombres, // Las categorías del gráfico
                    datasets: [
                        {
                            label: 'Monto Total',
                            data: montosTotales,
                            backgroundColor: '#0109f9',
                            borderWidth: 1,
                            borderColor: '#0109f9',
                        },
                        {
                            label: 'Monto Gastado',
                            data: montosGastados,
                            backgroundColor: '#4895ef',
                            borderWidth: 1,
                            borderColor: '#4895ef',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            title: { display: true, text: 'Categoría' },
                            ticks: { color: '#333333' }
                        },
                        y: {
                            title: { display: true, text: 'Monto' },
                            beginAtZero: true,
                            ticks: { color: '#333333' }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#333333',
                            }
                        }
                    }
                }
            });
        },
        error: function (xhr, status, error) {
            console.error("Error en la solicitud AJAX:", error);
        }
    });
}
