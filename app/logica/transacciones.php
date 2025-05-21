<?php
header('Content-Type: application/json');

include_once __DIR__ . "/../config/Database.php";
include_once __DIR__ . "/encripDesen.php";
include_once __DIR__ . "/../API_Banco/API_Llamada.php";
include_once __DIR__ . "/tokenJWT.php";

configurarSesion();

$conexion = new Database();
$conexion->obtenerConexion();

$api = new API_Llamada();

if (!$conexion) {
    die("Error de conexión a la base de datos.");
}
switch ($_POST["accion"]) {
    
    case "bd": //muestra las transacciones de la asisfin
        if ($_POST["filtro"]) {
            if ($_POST["filtro"] === "*") { //muestar toda las transacciones
                $arrayTransac = $conexion->consultar(
                    "SELECT t.*, 
                    IFNULL(t.descripcion, c.nombre) AS descripcion
                    FROM transaccion t
                    LEFT JOIN categorias c ON t.idCategoria = c.id
                    WHERE t.idUsuario = :idUsuario 
                    ORDER BY t.fecha " . $_POST["orden"],
                    ["idUsuario" => $_SESSION['idUsuario']]
                );
                echo json_encode($arrayTransac);
                return;
            } else { //muestra las transacciones por filtro y ordenanas 
                $arrayTransac = $conexion->consultar(
                    "SELECT t.*, 
                        IFNULL(t.descripcion, c.nombre) AS descripcion
                 FROM transaccion t
                 LEFT JOIN categorias c ON t.idCategoria = c.id
                 WHERE t.idUsuario = :idUsuario 
                   AND t.tipo = :tipo 
                 ORDER BY t.fecha " . $_POST["orden"],
                    ["idUsuario" => $_SESSION['idUsuario'], "tipo" => $_POST["filtro"]]
                );
                echo json_encode($arrayTransac);
                return;
            }
        }
        break;

    case "banco": //muestra las transacciones del banco

        $resultado = $conexion->consultar( //asisfin
            "SELECT nIdentificacion FROM usuarios WHERE id = :idUsuario",
            ["idUsuario" => $_SESSION['idUsuario']]
        );

        $nIdentificacion = desencriptar($resultado[0]["nIdentificacion"]);

        $datosUsuario = [
            'accion' => 'obtenerUsuario',
            'numero_documento' => $nIdentificacion,
        ];

        $respuestaApi = $api->llamarAPI($datosUsuario); //banco

        $datosUsuarioTransferencia = [
            'accion' => 'obtenerTransferencias',
            'cuenta_bancaria' => $respuestaApi['data']['cuenta_bancaria'],
            'filtro' => $_POST["filtro"],
            'orden' => $_POST["orden"],
        ];

        $respuestaVerTransferencia = $api->llamarAPI($datosUsuarioTransferencia);

        echo json_encode($respuestaVerTransferencia);
        break;

    case "saldo": //muestra el saldo y el iban del usuario
        $resultado = $conexion->consultar( //asisfin
            "SELECT nIdentificacion FROM usuarios WHERE id = :idUsuario",
            ["idUsuario" => $_SESSION['idUsuario']]
        );

        $nIdentificacion = desencriptar($resultado[0]["nIdentificacion"]);

        $datosUsuario = [
            'accion' => 'obtenerUsuario',
            'numero_documento' => $nIdentificacion,
        ];

        $respuestaApi = $api->llamarAPI($datosUsuario); //banco

        $arrayUsuarios = array(
            'iban' => $respuestaApi['data']["cuenta_bancaria"],
            'saldo' => $respuestaApi['data']["saldo"]
        );

        echo json_encode($arrayUsuarios);

        break;
}
