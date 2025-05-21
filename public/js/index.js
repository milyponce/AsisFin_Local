// index.js

$(document).ready(iniciar);

function iniciar() {
    verificarSesion().then(function (sesionIniciada) {
        if (sesionIniciada) { // Si la sesión está activa
            $(".btn-index").hide();
            $("#perfilOpciones").css("display", "block");
            $(".menuOculto").css("display", "block");
            
            cargarScript("js/perfilUsuario.js", () => {
                mostrarHistorial();
                mostrarInfo();
                recomendaciones();
            });
        }
    });

    document.getElementById("cerrarSesion").addEventListener("click", cerrar);
}

function cargarScript(src, callback) {
    const script = document.createElement("script");
    script.src = src;
    script.onload = callback;
    document.head.appendChild(script);
}

// Función para cerrar sesión
function cerrar() {

    $.ajax({
        type: "POST",
        url: "php/rutasPagPHP.php",
        data: {
            pag: "indexPHP",
            accion: "cerrarSeion",
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            $("#menuOculto").hide();
            $("#perfilOpciones").hide();
            window.location.href = "index.html";
        },
        error: function (xhr, status, error) {
            console.error("Error al cerrar sesión:", error);
        }
    });
}

function verificarSesion() {
    return new Promise(function (resolve, reject) {
        $.ajax({
            type: "POST",
            url: "php/rutasPagPHP.php", // Corregido
            data: {
                pag: "indexPHP",
                accion: "comprobarSesion",
                nocache: Math.random()
            },
            dataType: "json",
            success: function (respuesta) {
                // Verificamos si la respuesta es true (como booleano o string)
                if (respuesta.success === true || respuesta.success === "true") {
                    resolve(true);
                } else {
                    resolve(false);
                }
            },
        });
    });
}

function mostrarAdvertencia() { // muestra vetana de advertencia cuando no se ha iniciado sesion
    const alertaHTML = `
                <div class="fullscreen-alert">
                    <div class="alert-content">
                        <div class="alert-title">¡Acceso Restringido!</div>
                        <div class="alert-message">
                            Debes iniciar sesión para acceder a esta página.
                        </div>
                        <button class="btn btn-light btn-lg" onclick="redirigirLogin()">
                            Iniciar Sesión
                        </button>
                    </div>
                </div>
            `;

    // Añadir la alerta al body
    $('body').append(alertaHTML);

    // Prevenir scroll en el body
    $('body').css('overflow', 'hidden');
}

function redirigirLogin() { // cuando hace click en iniciar sesion de la ventana de advertencia
    // Redireccionar a la página de login
    window.location.replace("inicioSesion.html");
}

function mostrarVentana(mensaje) {
    // Crear contenedor si no existe
    let $container = $("#alertas-container");
    if (!$container.length) {
        $("body").prepend('<div id="alertas-container" class="p-3" style="position:fixed;top:30%;left:50%;transform:translate(-50%,-50%);z-index:1050;width:90%;max-width:500px;"></div>');
        $container = $("#alertas-container");
    }
    $container.empty(); // Limpiar si ya existía
    $("body").append('<div id="alertas-backdrop"></div>');

    // Crear alerta
    const $toast = $(`
        <div class="toast-container">
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header" style="background: var(--gradient-primary); color: var(--light-color);">
                    <strong class="me-auto"><i class="bi bi-check-circle-fill me-2"></i>Éxito</strong>
                </div>
                <div class="toast-body" style="background-color: var(--light-color);">
                ${mensaje}
                </div>
            </div>
        </div>
    `);
    $container.append($toast);

    // Cerrar al hacer clic en la "X"
    $(".btn-close").on("click", () => {
        $("#alertas-backdrop, #alertas-container").remove();
    });

    // Inicializar toast con Bootstrap (si aplica)
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t, { autohide: false }));
    }
}