
// Hacer funciones globales para usarlas desde otros archivos
window.mostrarHistorial = mostrarHistorial;
window.mostrarInfo = mostrarInfo;
window.recomendaciones = recomendaciones;

// Llamada a inicio SOLO si estás en la página de perfil
$(document).ready(() => {
    if (document.getElementById("idNombreBienvenida")) { // Asegúrate de tener este ID en perfil.html
        inicio();
    }
});

function inicio() {
    verificarSesion().then(sesionActiva => {
        if (!sesionActiva) {
            mostrarAdvertencia();
        }
    });

    mostrarHistorial();
    mostrarInfo();
    $(".editarPerfil").click(activarInput);
    $("#idEliminarCuenta").click(eliminarCuenta);
    recomendaciones();
}

// ... (el resto del código no se toca: mostrarHistorial, mostrarInfo, recomendaciones, etc.)


//eliminar cuenta
function eliminarCuenta(e) {
    e.preventDefault();

    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "perfilUsuario",
            accion: "eliminarUsua"
        },
        success: function (respuesta) {
            mostrarVentana("Su cuenta se está eliminando, por favor espere...");
                if (respuesta.status === "success") {
                    setTimeout(function () {
                        cerrar(); // Función que deberías tener definida para redirigir o cerrar sesión
                    }, 4000);
                } else {
                    mostrarVentana("Hubo un problema: " + res.mensaje);
                    console.error("Mensaje del servidor:", res.mensaje);
                }
        },
        error: function (xhr, status, error) {
            console.error("Error al eliminar la cuenta:", error);
        }
    });
}



function mostrarHistorial() {
    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "perfilUsuario",
            accion: "historialUsua",
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            respuesta.forEach(function (h) {
                var input = $('<input>', {
                    type: 'text',
                    value: h.fechaInicio,
                    class: 'form-control',
                    style: "text-align: center"
                });
                $('#idHistoria').append(input);
            });
            $("input").prop("disabled", true); // Bloquea los inputs
        }
    });
}

function mostrarInfo() {
    $("input").prop("disabled", true); // Bloquea los inputs
    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "perfilUsuario",
            accion: "infUsuario",
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            if (respuesta) { // Rellena los input con la informacion de usuario
                $("#idNombreBienvenida").text(respuesta.nombre);
                $(".idUsuario").val(localStorage.getItem("idUsuario"));
                $("#idNombre").val(respuesta.nombre);
                $("#idCorreo").val(respuesta.correo);
                $("#idContra").val("*********");
                $("#id2FA").val(respuesta.correo);
                $("#idAlerNotifi").val(respuesta.correo);
                $("#idClave").val("******");
                $("#nIdentificador").val(respuesta.nIdentificacion);
                $("#idIban").val(respuesta.iban);
            }
        },
        error: function (respuesta) {
            console.log('Error: ' + respuesta);
        }
    });
}

function activarInput(e) {
    e.preventDefault();

    let boton = $(this);
    let email = boton.data("email");
    let pass = boton.data("pass");
    let cuentaBancaria = boton.data("cuenta");

    // Si está en modo "Guardar", actualiza
    if (boton.text() === "ACTUALIZAR") {
        // Actualizar correo
        if (email) {
            let nuevoCorreo = $("#idCorreo").val();

            $("#infoCorreo").css("display", "block");

            if (!nuevoCorreo) {
                $("#infoCorreo").text("Rellene el campo");
            } else if (!/^[\w\.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(nuevoCorreo)) {
                $("#infoCorreo").text("Formato inválido");
            } else {
                $.ajax({
                    url: "../public/php/rutasPagPHP.php",
                    type: 'POST',
                    data: {
                        pag: "perfilUsuario",
                        accion: 'actualizar',
                        actualizar: 'correo',
                        valor: nuevoCorreo
                    },
                    success: function (response) {
                        if (response.status === "success") {
                            $("#infoCorreo").text("Se ha actualizado el correo");
                            $("#idCorreo").prop("disabled", true);
                            boton.text("EDITAR CORREO");
                        } else {
                            $("#infoCorreo").text(response.mensaje);
                        }
                    }
                });
            }
        }

        // Actualizar contraseña
        if (pass) {
            let nuevaContra = $("#idContra").val();

            $("#infoContra").css("display", "block");

            if (!nuevaContra) {
                $("#infoContra").text("Rellene el campo");
            } else if (!/^(?=.*[a-zA-Z])(?=.*\d)[A-Za-z\d]{8,}$/.test(nuevaContra)) {
                $("#infoContra").text("Formato inválido. Debe tener 8 caracteres, incluyendo letras y números");
            } else {
                $.ajax({
                    url: '../public/php/rutasPagPHP.php', // Modificar esta ruta
                    type: 'POST',
                    data: {
                        pag: "perfilUsuario",
                        accion: 'actualizar',
                        actualizar: 'contrasena',
                        valor: nuevaContra
                    },
                    success: function (response) {
                        if (response.status === "success") {
                            $("#infoContra").text("Se ha actualizado la contraseña");
                            $("#idContra").prop("disabled", true);
                            boton.text("EDITAR CONTRASEÑA");
                        } else {
                            $("#infoContra").text(response.mensaje);
                        }
                    }
                });
            }
        }

        // Actualizar cuenta bancaria nIdentificacor y claveAcceso
        if (cuentaBancaria) {
            let nuevaClave = $("#idClave").val();
            let nuevoIdentificador = $("#nIdentificador").val();

            $("#infoCuenta").css("display", "block");

            $.ajax({
                url: '../public/php/rutasPagPHP.php', // Modificar esta ruta
                type: 'POST',
                data: {
                    pag: "perfilUsuario",
                    accion: 'actualizar',
                    actualizar: "cuentaBancaria",
                    clave: nuevaClave,
                    identificador: nuevoIdentificador
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === "success") {
                        $("#infoCuenta").css("display", "block");
                        $("#infoCuenta").text("Se ha actualizado la cuenta bancaria");
                        $("#idClave").prop("disabled", true);
                        $("#nIdentificador").prop("disabled", true);
                        boton.text("EDITAR CUENTA BANCARIA");
                    } else {
                        $("#infoCuenta").text(response.mensaje);
                    }
                },
                error: function (respuesta) {
                    console.log(respuesta);
                }
            });
        }
        return;
    }

    $(".editarPerfil").not(this).css("display", "none");

    $("input").prop("disabled", true); // Primero, deshabilitar todos los inputs

    if (email) {
        $("#idCorreo").prop("disabled", false);
    }
    if (pass) {
        $("#idContra").prop("disabled", false);
    }
    if (cuentaBancaria) {
        $("#nIdentificador").prop("disabled", false);
        $("#idClave").prop("disabled", false);
    }

    // Cambiar el texto del botón de EDITAR... a ACTUALIZAR"
    boton.text("ACTUALIZAR");
}

function recomendaciones() {
    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "recomendaciones",
            "nocache": Math.random()
        },
        dataType: "text",
        success: function (respuesta) {
            $("#idListaRecomenFinanciera").html(respuesta);
        }
    });
}