<?php
// Indica que la respuesta del servidor será en formato JSON
header('Content-Type: application/json');
// Permite el acceso desde cualquier origen (CORS)
header('Access-Control-Allow-Origin: *');
// Especifica que solo se permiten peticiones con el método POST
header('Access-Control-Allow-Methods: POST');
// Permite que el cliente envíe la cabecera Content-Type (necesaria para enviar JSON en el cuerpo de la petición)
header('Access-Control-Allow-Headers: Content-Type');

//Incluir archivos php donde se encuentran clases y funciones para el funcionamiento
include_once __DIR__ . "/../config/Database.php";
include_once __DIR__ . "/encripDesen.php";
include_once __DIR__ . "/tokenJWT.php";
include_once __DIR__ . '/../API_Banco/API_Llamada.php';


// Validar la petición HTTP 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido']);
    exit();
}
/*
// Verificar que se envíen todos los parámetros
if (!isset(
    $_POST['tipoPago'],
    $_POST['IbanDestino'],
    $_POST['monto'],
    $_POST['concepto'],
    $_POST['categoria'],
    $_POST['tipoTransfe'],
)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos requeridos']);
    exit();
}*/

// Validar que el monto sea un número positivo
if (!is_numeric($_POST['monto']) || floatval($_POST['monto']) <= 0) {
    echo json_encode(['status' => 'error', 'mensaje' => 'El monto debe ser un número positivo']);
    exit();
}

configurarSesion();

// Inicializar la BD Asisfin
$conexion = new Database();
$conexion->obtenerConexion();

$_SESSION['idUsuario'] = 16;
// tipo de pago para saber en que bd guardar
switch ($_POST['tipoPago']) {

    case 'efectivo': //guardar en bd asisfin

        if (isset($_POST['concepto']) && !empty($_POST['concepto'])) {
            // Si hay concepto, se guarda el tipo de transacción (ingreso o gasto)
            $respuesta = $conexion->insertar(
                "transaccion",
                ['idUsuario', 'tipo', 'monto', 'descripcion'],
                [$_SESSION['idUsuario'], $_POST['tipoTransfe'], $_POST['monto'], $_POST['concepto']]
            );
        } else {
            // Si no hay concepto, pero hay categoría, se guarda automáticamente como gasto
            $respuesta = $conexion->insertar(
                "transaccion",
                ['idUsuario', 'tipo', 'monto', 'idCategoria'],
                [$_SESSION['idUsuario'], "gasto", $_POST['monto'], $_POST['categoria']]
            );
        }

        // Verificar si la inserción fue exitosa
        if ($respuesta) {
            echo json_encode(['status' => 'success', 'mensaje' => 'Transacción Exitosa']);
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'Hubo un error al procesar la transacción']);
        }
        break;

    case 'transferencia': // tranferencia se hace con la api

        // Inicializar la API
        $api = new API_Llamada();

        // Obtener número de identificación del usuario
        $query = "SELECT * FROM usuarios WHERE id = :idUsuario";
        $param = [":idUsuario" => $_SESSION['idUsuario']];
        $arrayUsuario = $conexion->consultar($query, $param);

        if (empty($arrayUsuario)) {
            echo json_encode(['status' => 'error', 'mensaje' => 'No se encontró el usuario']);
            exit();
        }

        // Obtener número de cuenta del usuario
        $datosUsuario = [
            'accion' => 'obtenerUsuario',
            'numero_documento' => desencriptar($arrayUsuario[0]['nIdentificacion'])
        ];
        $respuestaApi = $api->llamarAPI($datosUsuario);

        if (!isset($respuestaApi['data']['cuenta_bancaria'])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo obtener la cuenta bancaria']);
            exit();
        }

        // Preparar datos para la transferencia
        $datosTransferencia = [];

        // Si hay concepto
        if (isset($_POST['concepto']) && !empty($_POST['concepto'])) {
            $datosTransferencia = [
                'accion' => 'crearTransferencia',
                'cuenta_origen' => $respuestaApi['data']['cuenta_bancaria'],
                'cuenta_destino' => "ES" . $_POST['IbanDestino'],
                'monto' => $_POST['monto'],
                'concepto' => $_POST['concepto'],
            ];
        } else {
            // Si se ha seleccionado categoría
            $query = "SELECT nombre FROM categorias WHERE id = :idCategoria";
            $param = [":idCategoria" => $_POST['categoria']];
            $arrayCategoria = $conexion->consultar($query, $param);

            $nombreCategoria = !empty($arrayCategoria) ? $arrayCategoria[0]['nombre'] : "Sin categoría";

            $datosTransferencia = [
                'accion' => 'crearTransferencia',
                'cuenta_origen' => $respuestaApi['data']['cuenta_bancaria'],
                'cuenta_destino' => "ES" . $_POST['IbanDestino'],
                'monto' => $_POST['monto'],
                'concepto' => $nombreCategoria,
            ];
        }

        // Realizar la transferencia
        $respuestaTransferencia = $api->llamarAPI($datosTransferencia);
        
        // Asegurarse de que la respuesta sea JSON
        if ($respuestaTransferencia['status'] === 'success') {
            echo json_encode(['status' => 'success', 'mensaje' => 'Transacción Exitosa']);
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'Hubo un error al procesar la transacción']);
        }

        break;

    default:
        echo json_encode(['status' => 'error', 'mensaje' => 'Tipo de pago no válido']);
        exit();
}
