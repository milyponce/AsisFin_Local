<?php
header('Content-Type: application/json');

// Corregir las rutas añadiendo la barra "/"
include_once __DIR__ . "/../config/Database.php";
include_once __DIR__ . "/../config/claves.php";
include_once __DIR__ . "/encripDesen.php";
include_once __DIR__ . "/tokenJWT.php";
include_once __DIR__ . "/../API_Banco/API_Llamada.php";

// Llamar a la función para cargar las variables de entorno
cargarEnv();

$conexion = new Database();
$conexion->obtenerConexion();

$api = new API_Llamada();

if (!$conexion) {
    die("Error de conexión a la base de datos.");
}

configurarSesion();

switch ($_POST['accion']) {
    //mostrar informacion del usuario
    case "infUsuario":
        $query = "SELECT * FROM usuarios WHERE id = :id"; //bd asisfin
        $params = [':id' => $_SESSION['idUsuario']];

        $arrayDatos = $conexion->consultar($query, $params);

        $datosUsuario = [
            'accion' => 'obtenerUsuario',
            'numero_documento' => desencriptar($arrayDatos[0]["nIdentificacion"]),
        ];

        $respuestaApi = $api->llamarAPI($datosUsuario); //bd banco

        $arrayUsuarios = array( //array con la informacion extraida de las 2 bd
            'nombre' => $arrayDatos[0]["apellidos"] . " " . $arrayDatos[0]["nombre"],
            'nIdentificacion' => desencriptar($arrayDatos[0]["nIdentificacion"]),
            'correo' => $arrayDatos[0]["correo"],
            'iban' => $respuestaApi['data']["cuenta_bancaria"]
        );

        echo json_encode($arrayUsuarios);
        break;

    case "actualizar":
        //Actualizar datos de usuario: correo, contraseña, nIdentificacion, claveAcceso
        if ($_POST['actualizar'] === 'correo' || $_POST['actualizar'] === 'contrasena') {
            $campo = $_POST['actualizar'];
            $valor = $_POST["valor"];

            if ($campo === 'contrasena') { // encriptan contraseña ya que es datos sensible
                $valor = password_hash($valor, PASSWORD_DEFAULT);
            } else { // correo
                $query = "SELECT correo FROM usuarios WHERE correo = :correo";
                $params = [':correo' => $valor];

                // verificar que el correo no exitas ya en la bd, por que el correo es unico
                if (count($arrayDatos = $conexion->consultar($query, $params)) > 0) {
                    echo json_encode([
                        'status' => 'error',
                        'mensaje' => 'El correo introducido ya existe.'
                    ]);
                    exit();
                }
            }
            //ejecutar la actualizacion
            $query = "UPDATE usuarios SET {$campo} = :valor WHERE id = :id";
            $params = [
                ':id' => $_SESSION['idUsuario'],
                ':valor' =>  $valor
            ];
            $resultado = $conexion->ejecutar($query, $params);
        } else if ($_POST['actualizar'] == 'cuentaBancaria') {
            //verificar si existe el usuario en la bd de banco
            $datosUsuario = [
                'accion' => 'obtenerUsuario',
                'numero_documento' => $_POST['identificador']
            ];
            $respuestaApi = $api->llamarAPI($datosUsuario);

            // verificar que la respuesta de la api se igual a la que ingreso el usuario
            if (
                isset($respuestaApi['numero_documento'], $respuestaApi['clave']) &&
                $respuestaApi['numero_documento'] === $_POST['identificador'] &&
                $respuestaApi['clave'] === $_POST['clave']
            ) {
                $query = "UPDATE usuarios SET nIdentificacion = :identificador, claveAcceso = :clave WHERE id = :id";
                $params = [
                    ':id' => $_SESSION['idUsuario'],
                    ':identificador' => encriptar($_POST['identificador']),
                    ':clave' => encriptar($_POST['clave'])
                ];
                $resultado = $conexion->ejecutar($query, $params);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Identificador o clave incorrectos.'
                ]);
            }
            exit();
        }

        // verificar si se ha ejecutado correctamente la actualizacion
        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'mensaje' => 'Actualización exitosa'
            ]);
            exit();
        } else {
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Error al actualizar la base de datos'
            ]);
            exit();
        }

        break;

    case "historialUsua":
        //historia de inicio de sesion de usuario
        $query = "SELECT fechaInicio FROM sesiones WHERE idUsuario = :id";
        $params = [':id' => $_SESSION['idUsuario']];

        $arrayDatos = $conexion->consultar($query, $params);

        echo json_encode($arrayDatos);
        break;

    case "eliminarUsua":
        // Eliminar usuario por ID guardado en la sesión
        if (isset($_SESSION['idUsuario'])) {
            $resultado = $conexion->ejecutar("DELETE FROM usuarios WHERE id = :id", [
                ":id" => $_SESSION['idUsuario']
            ]);

            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'mensaje' => 'Se ha eliminado el usuario'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'mensaje' => 'Error al eliminar el usuario'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'mensaje' => 'Usuario no autenticado'
            ]);
        }
        exit();
        break;
}
