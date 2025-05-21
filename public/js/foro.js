$(document).ready(inicio);

function inicio() {
    verificarSesion().then(sesionActiva => {
        if (!sesionActiva) {
            mostrarAdvertencia();
        }
    });

    $("#btn_publicar").click(crearPost);
    $(document).on("click", "#btn_comentar", crearComentario);

    cargarPost();
}

function crearPost(e) {
    e.preventDefault();

    let titulo = $("#idTitulo").val();
    let contenido = $("#idContenido").val();

    if (titulo === "" && contenido === "") {
        $("#info").css("display", "block");
        $("#info").text("Rellena todos los campos.");

    } else {

        $("#info").css("display", "none");
        $("#info").text("");

        $.ajax({
            type: "POST",
            url: "../public/php/rutasPagPHP.php",
            data: {
                pag: "foro",
                accion: "crearPost",
                titulo: titulo,
                contenido: contenido,
                nocache: Math.random()
            },
            dataType: "json",
            success: function (respuesta) {
                $("#info").css("display", "block");
                $("#info").text(respuesta.mensaje);
                $("#idTitulo").val(""); $("#idContenido").val("");
                cargarPost();
            },
            error: function (response) {
                console.error(response);
            }
        });
    }
}

function cargarPost() {
    // Corregido: Ahora vaciamos el contenedor correcto
    $("#id_posts").empty();

    $.ajax({
        type: "POST",
        url: "../public/php/rutasPagPHP.php",
        data: {
            pag: "foro",
            accion: "mostrarPost",
            nocache: Math.random()
        },
        dataType: "json",
        success: function (respuesta) {

            respuesta.forEach(function (post) { // div de cada post
                let div = $('<div>', { class: 'col-md-8 mx-auto mb-4 card card-body' });

                $('<h5>', { class: 'card-title text-center', text: post.post_titulo }).appendTo(div);
                $('<h6>', { class: 'card-subtitle text-muted mb-2', text: `Publicado por ${post.autor_post}` }).appendTo(div);
                $('<p>', { class: 'card-text', text: post.post_contenido }).appendTo(div);

                $('<h4>', { class: 'card-title text-center', text: "Comentarios" }).appendTo(div);

                $.ajax({ //ajax para los comentarios
                    type: "POST",
                    url: "../public/php/rutasPagPHP.php",
                    data: {
                        pag: "foro",
                        accion: "mostrarComentarios",
                        idPost: post.post_id,
                        nocache: Math.random()
                    },
                    dataType: "json",
                    success: function (respuestaComentario) {
                        
                        respuestaComentario.forEach(function (comentario) {
                            let divComentario = $('<div>', { class: 'm-3 p-3 border rounded shadow-sm bg-white' });

                            $('<h6>', {class: 'card-subtitle text-muted mb-2 small', text: `Publicado por ${comentario.autor_comentario}`}).appendTo(divComentario);
                            $('<p>', {class: 'card-text small', text: comentario.comentario_contenido}).appendTo(divComentario);
                            $(divComentario).appendTo(div);
                        });
                    },
                    error: function (respuestaComentario) {
                        console.error(respuestaComentario);
                    }
                });

                let formulario = `
                    <div class="mb-2">
                        <textarea class="form-control" id="idComentario_${post.post_id}" placeholder="Escribe un comentario..."></textarea>
                    </div>
                    <button type="submit" id="btn_comentar" data-id_post="${post.post_id}" class="btn btn-primary">Comentar</button>
                `;

                $('<form>').html(formulario).appendTo(div);

                $(div).appendTo("#id_posts");
            });
        },
        error: function (response) {
            console.error(response);
        }
    });
}

function crearComentario(e) {
    e.preventDefault();
    let boton = $(e.target);
    let idPost = boton.data("id_post");
    
    // Corregido: Obtener el textarea específico para este post
    let comentario = $(`#idComentario_${idPost}`).val();

    if (comentario === "") {
        window.alert("Escribe un comentario.");
    } else {
        $.ajax({
            type: "POST",
            url: "../public/php/rutasPagPHP.php",
            data: {
                pag: "foro",
                accion: "crearComentario",
                comentario: comentario,
                idPost: idPost,
                nocache: Math.random()
            },
            dataType: "json",
            success: function (respuesta) {
                $("#info").css("display", "block");
                $(`#idComentario_${idPost}`).val("");
                cargarPost();
            },
            error: function (response) {
                console.error(response);
            }
        });
    }
}