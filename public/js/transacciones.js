$(document).ready(inicio);

function inicio() {
    verificarSesion().then(sesionActiva => {
        if (!sesionActiva) {
            mostrarAdvertencia();
        }
    });
    
    mostraBD();
    mostarAPI();
    //cambios en los select para bd y api
    $('#idfiltro').on('change', function(){mostraBD(); mostarAPI(); });
    $('#idOrden').on('change', function(){mostraBD(); mostarAPI(); });

    saldo_Ncuenta();
}

function mostraBD() {
    $('#transaccionBodyAsisFin').empty();

    let filto = $('#idfiltro').val();
    let orden = $('#idOrden').val();

    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag:"transacciones",
            accion: "bd",
            filtro: filto,
            orden: orden,
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            $(respuesta).each(function () {
                let tr = $("<tr/>");
                $("<td/>").text(this.descripcion).appendTo(tr);
                $("<td/>").text(this.monto).appendTo(tr);
                $("<td/>").text(this.fecha).appendTo(tr);
                $(tr).appendTo("#transaccionBodyAsisFin");
            })
        },error: function (respuesta) {
            console.log(respuesta);
        }
    });
}

function mostarAPI() {
    $('#transaccionBodyBanco').empty();

    let filto = $('#idfiltro').val();
    let orden = $('#idOrden').val();

    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "transacciones",
            accion: "banco",
            filtro: filto,
            orden: orden,
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            $(respuesta.mensaje).each(function () {
                let tr = $("<tr/>");
                $("<td/>").text(this.concepto).appendTo(tr);
                $("<td/>").text(this.monto).appendTo(tr);
                $("<td/>").text(this.fecha).appendTo(tr);
                $(tr).appendTo("#transaccionBodyBanco");
            })
        }
    });
}

function saldo_Ncuenta() {
    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "transacciones",
            accion: "saldo",
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {
            $(respuesta).each(function () {
                $("#saldo").text(this.saldo + " €");
                $("#iban").text(this.iban);
            })
        }
    });
}