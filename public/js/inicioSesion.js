$(document).ready(function () {
    $("#btn_crear_cuenta").click(crearCuenta); //lleva al crear cuenta
    $("#btn_inicio_sesion").click(iniciarSesion);//lleva a iniciar sesion
    $("#btn_compro_pin").click(pin2FA); //lleva comprobar pin enviado por correo

    // ver contraseña de crear sesion
    $('#togglePassword').click(function () { verPas("idContra", this); });

    // ver clave de crear cuenta
    $('#toggleClave').click(function () { verPas("idClave", this); });

    // ver contraseña de inicio sesion
    $('#togglePass').click(function () { verPas("idPass", this); });
});

function verPas(id, toggleElement) { // ver contraseña
    // Obtiene el elemento de entrada por el id
    const passwordInput = document.getElementById(id);
    //Encuentra el elemento de ícono donde esta el botton
    const icon = toggleElement.querySelector('i');

    //si esta oculta la contrasena
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text'; //Cambia el tipo de entrada a 'text' para mostrar la contraseña
        // cambia el css para el icono
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password'; // Cambia el tipo de entrada a 'password' para ocultar la contraseña
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Comprobacion de inicia sesion para que cumpla cierto requisito
function comprobacionSesion() {
    let error = false;

    const campo = {
        idEmail: "#error_email",
        idPass: "#error_pass",
    };

    for (const [camp, idErr] of Object.entries(campo)) {
        let valor = $(`#${camp}`).val().trim();  // .trim() elimina espacios en blanco
        if (!valor) {
            error = true;
            $(idErr).text("Rellenar campo");
        } else if (camp === "idPass" && !/^(?=.*[a-zA-Z])(?=.*\d)[A-Za-z\d]{8,}$/.test(valor)) {
            error = true;
            $(idErr).text("Formato inválido");
        } else {
            $(idErr).text("");
        }
    }
    return error;
}

// llamada ajax para comprobar credenciales
function iniciarSesion(e) {
    e.preventDefault();

    let correo = $("#idEmail").val();
    let contraseña = $("#idPass").val();

    if (!comprobacionSesion()) {
        $("#cargaSVG").css("display", "block");
        $.ajax({
            type: "POST",
            url: "../public/php/rutasPagPHP.php",
            data: {
                pag: "inicioSesion",
                accion: "credeciales",
                correo: correo,
                pass: contraseña,
                nocache: Math.random()
            },
            dataType: "json",
            success: function (respuesta) {               
                $("#infoForm").text(respuesta);
                if (respuesta.status === 'success') {
                    $(".alert").css("display", "none");
                    $("#credencial").hide();
                    $(".enlace").hide();
                    $(".pinOculto").css("display", "block");
                    let correCifrado = correo.slice(0, 3) + "*".repeat(10) + correo.slice(-4);
                    $("#idCorreoPin").text(correCifrado);
                } else {
                    $(".alert").css("display", "block");
                    $("#info").text(respuesta.mensaje);
                }
            },
            error: function (respuesta) {
                console.log("Error en la llamada AJAX:", respuesta);
            }
        });
    }
}

// Comprobacion de los campos para crear cuenta para que cumpla cierto requisito
function comprobacionCuenta() {
    let error = false;
    const campos = {
        idNombre: "#error_nombre",
        idApellidos: "#error_apellidos",
        idCorreo: "#error_correo",
        idContra: "#error_contra",
        nIdentificacion: "#error_identificacion",
        idClave: "#error_clave",
        acceptTerms: "#error_terminos"
    };

    for (const [campo, idError] of Object.entries(campos)) {
        if (campo === "acceptTerms") {
            const checked = $("#acceptTerms").is(":checked");
            if (!checked) {
                error = true;
                $(idError).text("Debes aceptar los términos");
            } else {
                $(idError).text("");
            }
            continue; // saltamos al siguiente campo
        }

        let valor = $(`#${campo}`).val().trim();

        if (!valor) {
            error = true;
            $(idError).text("Rellenar campo");
        } else if ((campo === "idNombre" || campo === "idApellidos")
            && !/^[A-Za-zÑñáéíóúÁÉÍÓÚ\s]+$/.test(valor)) {
            error = true;
            $(idError).text("Solo se permiten letras");
        } else if (campo === "idCorreo" &&
            !/^[\w\.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(valor)) {
            error = true;
            $(idError).text("Formato de correo inválido");
        } else if (campo === "idContra" && !/^(?=.*[a-zA-Z])(?=.*\d)[A-Za-z\d]{8,}$/.test(valor)) {
            error = true;
            $(idError).text("Formato inválido");
        } else if (campo === "idClave" && !/^\d{6}$/.test(valor)) {
            error = true;
            $(idError).text("Clave incorrecta. Debe tener 6 dígitos.");
        } else {
            $(idError).text("");
        }
    }

    return error;
}

function crearCuenta(e) {
    e.preventDefault();

    if (!comprobacionCuenta()) {
        $.ajax({
            type: "POST",
            url:  "../public/php/rutasPagPHP.php", // Corregir URL
            data: {
                pag: "crearCuenta",
                nombre: $("#idNombre").val(),
                apellidos: $("#idApellidos").val(),
                correo: $("#idCorreo").val(),
                pass: $("#idContra").val(),
                nIden: $("#nIdentificacion").val(),
                claveAcceso: $("#idClave").val()
            },
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.status === 'success') {
                    $('#formCrearCuenta')[0].reset();

                    mostrarVentana("Se ha creado la cuenta correctamente. Configurando la cuenta..."); 

                    setTimeout(function () {
                       window.location.replace("inicioSesion.html");
                    }, 2000);
                } else {
                    $(".alert").css("display", "block");
                    $("#info").text(respuesta.mensaje || "Error desconocido");
                }
            },
            error: function (respuesta) {
                console.error("Error en la llamada AJAX:", respuesta); 
            }
        });
    }
}

// llamada ajax para comprobar el pin enviado por correo
function pin2FA(e) {
    e.preventDefault();

    let pin = $("#idPin").val();

    // Verificar si el campo del PIN está vacío
    if (!pin) {
        $("#infoPin").text("Rellena el campo");
        return;
    }
    
    $("#infoPin").text("");  // Limpiar el mensaje

    // Primera llamada AJAX para validar el PIN
    $.ajax({
        type: "POST",
        url:  "../public/php/rutasPagPHP.php",
        data: {
            pag: "inicioSesion",
            accion: "Pin",
            pin: pin,
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            if (respuesta.status === 'error') {
                $("#infoPin").css("display", "block");
                $("#infoPin").text(respuesta.mensaje);
            } else if (respuesta.status === 'success') {
                window.location.href =  "index.html";
            }
        },
        error: function (respuesta) {
            console.error("Error en la llamada AJAX:", respuesta);
        }
    });
}


