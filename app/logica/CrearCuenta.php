<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Corregir las rutas de inclusión
include_once __DIR__ . "/../config/Database.php";
include_once __DIR__ . "/encripDesen.php";
include_once __DIR__ . '/../API_Banco/API_Llamada.php';


// Validar la petición HTTP 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensaje' => 'Metodo no permitido']);
    exit();
}

$conexion = new Database();
$conexion->obtenerConexion();

// Verificar que todos los campos requeridos están presentes 
if (!isset(
    $_POST["nombre"],
    $_POST["apellidos"],
    $_POST["correo"],
    $_POST["pass"],
    $_POST["nIden"],
    $_POST["claveAcceso"]
)) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos requeridos']);
    exit(); 
}

// VERIFICAR SI EL CORREO YA EXISTE 
$query = "SELECT correo FROM usuarios WHERE correo = :correoUsuario";
$params = [':correoUsuario' => $_POST["correo"]];
$arrayDatos = $conexion->consultar($query, $params);

if (count($arrayDatos) > 0) {
    echo json_encode(['status' => 'error', 'mensaje' => 'El correo electrónico ya existe']);
    exit();
}

// VERIFICAR SI EL NÚMERO DE IDENTIFICACIÓN Y CLAVE DE ACCESO EXISTEN EN EL BANCO
$api = new API_Llamada();

$datosUsuario = [
    'accion' => 'obtenerUsuario',
    'numero_documento' => $_POST['nIden']
];

$respuestaAPi = $api->llamarAPI($datosUsuario);

// Agregar log para debugging
error_log("Respuesta API completa: " . print_r($respuestaAPi, true));

// Verificar que la respuesta sea válida y tenga el formato esperado
if (!isset($respuestaAPi) || !is_array($respuestaAPi) || !isset($respuestaAPi['status'])) {
    echo json_encode([
        'status' => 'error',
        'mensaje' => 'Respuesta inválida del banco',
        'debug' => substr(print_r($respuestaAPi, true), 0, 500) // Solo en desarrollo
    ]);
    exit();
}

// Verificar si hay error en la respuesta
if ($respuestaAPi['status'] === 'error') {
    echo json_encode($respuestaAPi);
    exit();
}

// Verificar las credenciales
if ($respuestaAPi['data']['clave'] !== $_POST['claveAcceso'] || 
    $respuestaAPi['data']['numero_documento'] !== $_POST['nIden']) {
    echo json_encode([
        'status' => 'error',
        'mensaje' => 'La clave de acceso o numero de identificacion no coincide'
    ]);
    exit();
} else {
    //inserta al nuevo usuario en la bd asisfin
    $crearCuenta = $conexion->insertar(
        "usuarios",
        ["nombre", "apellidos", "correo", "contrasena", "nIdentificacion", "claveAcceso"],
        [
            $_POST["nombre"],
            $_POST["apellidos"],
            $_POST["correo"],
            $_POST["pass"], // // encritar la contraseña en la clase de database ya que es datos sensible
            encriptar($_POST["nIden"]), // encritar el nIdentdad ya que es datos sensible
            encriptar($_POST["claveAcceso"]) // encritar el claveAcceso ya que es datos sensible
        ]
    );

    if ($crearCuenta) { // si ha insertado correctamenta el usuario
        echo json_encode(['status' => 'success', 'mensaje' => 'Se ha creado la cuenta correctamente']);
    } else { // si no se pudo insertar el usuario
        echo json_encode(['status' => 'error', 'mensaje' => 'Error al crear la cuenta en la base de datos']);
    }
    exit();
}
