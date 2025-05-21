$(document).ready(inicio);


function inicio() {
    verificarSesion().then(sesionActiva => {
        if (!sesionActiva) {
            mostrarAdvertencia();
        }
    });

    categorias();
    $("#btnTransferir").click(hacerTransferencia);
    manejoSelect();
}

// Manejador para concepto, categoría, tipo pago y transferencia
function manejoSelect() {
    // Para manejar el tipo de pago
    $('#idTipoPago').change(function () {
        const selectedValue = $(this).val();

        if (selectedValue === 'efectivo') {
            // Si es efectivo, ocultamos IBAN y mostramos tipo de transacción
            $('#divIBAN').hide();
            
            // Solo mostramos tipo de transacción si no hay categoría seleccionada
            if ($('#idCategoria').val() === '0' || $('#idCategoria').val() === null || $('#idCategoria').val() === undefined) {
                $('#divTransaccion').show();
            } else {
                $('#divTransaccion').hide();
            }
        } else if (selectedValue === 'transferencia') {
            //Si es transferencia mostramos el iban y ocultamos tipo de transferencia
            $('#divIBAN').show();
            $('#idIBAN').text('');

            $('#divTransaccion').hide();
            $('#error_transa').text('');
        }
    });

    // Para manejar concepto y categoría
    $("#idConcepto").on("input change", function () {
        const conceptoValue = $(this).val().trim();

        // Si hay concepto, ocultamos el div de categoría
        if (conceptoValue.length > 0) {
            $('#divCategoria').hide();
        } else {
            // Si no hay concepto, mostramos la categoría
            $('#divCategoria').show();
            $('#idCategoria').val('0');
            $('#error_categori').text('');
        }
    });

    // Para manejar cuando cambia la categoría
    $("#idCategoria").on("change", function () {
        const categoriaValue = $(this).val();

        if (categoriaValue !== '0') {
            // Si hay categoría seleccionada, ocultamos el concepto
            $('#divConcepto').hide();
            $('#idConcepto').val('');
            $('#error_concepto').text('');
            
            // También ocultamos el tipo de transacción independientemente del tipo de pago
            $('#divTransaccion').hide();
            $('#error_transa').text('');
        } else {
            // Si no hay categoría seleccionada, mostramos el concepto
            $('#divConcepto').show();

            // Mostramos el tipo de transacción si el tipo de pago es efectivo
            if ($('#idTipoPago').val() === 'efectivo') {
                $('#divTransaccion').show();
            }
        }
    });

    // Ejecutamos solo el cambio del tipo de pago al inicio
    $('#idTipoPago').trigger('change');

    // No ejecutamos los triggers de concepto y categoría automáticamente
    // Solo aplicamos lógica si ya tienen valores
    const conceptoValue = $('#idConcepto').val().trim();
    if (conceptoValue.length > 0) {
        $('#divCategoria').hide();
    }

    const categoriaValue = $('#idCategoria').val();
    if (categoriaValue !== '0' && categoriaValue !== null && categoriaValue !== undefined) {
        $('#divConcepto').hide();
        // Ocultamos también el tipo de transacción si hay categoría seleccionada
        $('#divTransaccion').hide();
        $('#error_transa').text('');
    }
}

// Función para cargar categorías
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
            const $categoria = $("#idCategoria");
            $categoria.append($("<option/>").val("0").text("Seleccione una Categoria"));

            respuesta.forEach(categoria => {
                $categoria.append($("<option/>").val(categoria.id).text(categoria.nombre));
            });
        },
        error: function (xhr, status, error) {
            console.error('Error al cargar categorías:', error);
            // Mostrar mensaje de error al usuario
            $("#error_categori").text("Error al cargar categorías");
        }
    });
}

// Función de validación
function comprobacionTrans() {
    // Reiniciar todos los mensajes de error
    $('#error_pago, #error_iban, #error_monto, #error_concepto, #error_categori, #error_transa').text('');

    let esValido = true;

    // Validar tipo de pago (siempre visible - select)
    const tipoPago = $('#idTipoPago').val();
    if (tipoPago === '0') {
        $('#error_pago').text('Seleccione una opción.');
        esValido = false;
    }

    // Validar IBAN (solo si transferencia está seleccionada y el div es visible - input)
    if (tipoPago === 'transferencia' && $('#divIBAN').is(':visible')) {
        const iban = $('#idIBAN').val().trim();
        if (!iban) {
            $('#error_iban').text('Rellene este campo.');
            esValido = false;
        } else if (!/^[0-9]{22}$/.test(iban)) {
            $('#error_iban').text('El IBAN debe contener exactamente 22 dígitos numéricos.');
            esValido = false;
        }
    }

    // Validar monto (siempre visible - input)
    const monto = $('#idMonto').val();
    if (!monto) {
        $('#error_monto').text('Rellene este campo.');
        esValido = false;
    } else if (parseFloat(monto) <= 0) {
        $('#error_monto').text('Ingrese un monto válido mayor que cero.');
        esValido = false;
    }

    // Comprobar si el concepto es visible y validar (textarea - input)
    if ($('#divConcepto').is(':visible')) {
        const concepto = $('#idConcepto').val().trim();
        if (!concepto) {
            $('#error_concepto').text('Rellene este campo.');
            esValido = false;
        }
    }

    // Comprobar si la categoría es visible y validar (select)
    if ($('#divCategoria').is(':visible')) {
        const categoria = $('#idCategoria').val();
        if (!categoria || categoria === '0') {
            $('#error_categori').text('Seleccione una opción.');
            esValido = false;
        }
    }

    // Comprobar si el tipo de transacción es visible y validar (select)
    if ($('#divTransaccion').is(':visible')) {
        const tipoTransaccion = $('#IdTipoTransaccion').val();
        if (tipoTransaccion === '0') {
            $('#error_transa').text('Seleccione una opción.');
            esValido = false;
        }
    }

    return esValido;

}

// Función para realizar la transferencia
function hacerTransferencia(e) {
    e.preventDefault();

    if (comprobacionTrans()) { // Solo procedemos si NO hay errores
        // Obtener los datos del formulario

        // Mostrar mensaje de procesamiento
        $("#info").css("display", "block");
        $("#info").text("Procesando transferencia...");

        // Realizar la solicitud AJAX
        $.ajax({
            type: "POST",
            url: "../public/php/rutasPagPHP.php", 
            data: {
                pag:"hacerTransferencia",
                tipoPago: $("#idTipoPago").val(),
                IbanDestino: $("#idIBAN").val(),
                monto: $("#idMonto").val(),
                concepto: $("#idConcepto").val(),
                categoria: $("#idCategoria").val(),
                tipoTransfe: $("#IdTipoTransaccion").val(),
            },
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    // Resetear el formulario y mostrar mensaje de éxito
                    $("#idformTransferir")[0].reset();
                    mostrarVentana("Se ha realizado la transferencia correctamente. Espere un momento..."); 

                    setTimeout(function () {
                        window.location.href = "transacciones.html";
                    }, 3000);

                } else {
                    // Mostrar mensaje de error
                    $("#info").text(response.mensaje || "Error en la transferencia");
                }
            },
            error: function (xhr, status, error) {
                console.error('Error en la transferencia:', error);
                console.error('Respuesta del servidor:', xhr.responseText);

                try {
                    // Intentar parsear la respuesta como JSON
                    const errorData = JSON.parse(xhr.responseText);
                    $("#info").text(errorData.mensaje || "Error al realizar la transferencia");
                } catch (e) {
                    // Si no se puede parsear, mostrar mensaje genérico
                    $("#info").text("Error al realizar la transferencia: " + error);
                }
            }
        });
    }
}
