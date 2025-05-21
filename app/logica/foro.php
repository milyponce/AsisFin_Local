<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Corregir las rutas de inclusión
include_once __DIR__ . "/../config/Database.php";
include_once __DIR__ . "/tokenJWT.php";

// Validar la petición HTTP 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'mensaje' => 'Metodo no permitido']);
    exit();
}

$conexion = new Database();
$conexion->obtenerConexion();

switch ($_POST["accion"]) {
    case 'crearPost':
        if (!isset($_POST["titulo"], $_POST["contenido"])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos requeridos']);
            exit();
        }

        configurarSesion();
        $crearPost = $conexion->insertar(
            "posts",
            ["autor_id", "titulo", "contenido"],
            [$_SESSION["idUsuario"], $_POST["titulo"], $_POST["contenido"]]
        );

        if ($crearPost) { // si ha insertado correctamenta el usuario
            echo json_encode(['status' => 'success', 'mensaje' => 'Se ha creado el post correctamente']);
        } else { // si no se pudo insertar el usuario
            echo json_encode(['status' => 'error', 'mensaje' => 'Error al crear al crear el post']);
        }
        break;
        
    case "mostrarPost":
        $query = " SELECT 
                    p.id AS post_id,
                    p.titulo AS post_titulo,
                    p.contenido AS post_contenido,
                    u.nombre AS autor_post
                FROM 
                    posts p
                JOIN 
                    usuarios u ON p.autor_id = u.id
                ORDER BY 
                    p.fecha DESC;";
        $arrayPostComentario = $conexion->consultarSinParams($query);
        echo json_encode($arrayPostComentario);
        break;

    case "mostrarComentarios":

        $query = " SELECT 
                    c.id_post AS post_id,
                    u.nombre AS autor_comentario,
                    c.contenido AS comentario_contenido,
                    c.fecha AS fecha_comentario
                FROM 
                    comentarios c
                JOIN 
                    usuarios u ON c.id_usuario = u.id
                WHERE 
                    c.id_post = :id_post 
                ORDER BY 
                    c.fecha ASC;";
        $arrayPostComentario = $conexion->consultar($query, [":id_post" => $_POST["idPost"]]);
        echo json_encode($arrayPostComentario);
        break;

        break;

    case "crearComentario":
        if (!isset($_POST["comentario"])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos requeridos']);
            exit();
        }

        configurarSesion();
        $crearComentario = $conexion->insertar(
            "comentarios",
            ["id_post", "id_usuario", "contenido"],
            [$_POST["idPost"], $_SESSION["idUsuario"],  $_POST["comentario"]]
        );

        if ($crearComentario) { // si ha insertado correctamenta el usuario
            echo json_encode(['status' => 'success', 'mensaje' => 'Se ha creado el comentario correctamente']);
        } else { // si no se pudo insertar el usuario
            echo json_encode(['status' => 'error', 'mensaje' => 'Error al crear al crear el comentario']);
        }
        break;
}
