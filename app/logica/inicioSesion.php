<?php
// Usar rutas absolutas con __DIR__
include_once __DIR__ . "/../config/Database.php";
include_once __DIR__ . "/PHPMail.php";
include_once __DIR__ . "/../config/claves.php";
include_once __DIR__ . "/tokenJWT.php";

// Llamar a la función para cargar las variables de entorno
cargarEnv();

$conexion = new Database();
$db = $conexion->obtenerConexion();

if (!$db) {
    die("Error de conexión a la base de datos.");
}

configurarSesion(); // Se asegura de iniciar la sesión antes del switch

switch ($_POST["accion"]) {
    case "credeciales": // verificar las credenciales 
        if (empty($_POST["correo"]) || empty($_POST["pass"])) {
            die("Error: Datos incompletos");
        }

        // bd asisfin
        $query = "SELECT id, nombre, correo, contrasena FROM usuarios WHERE correo = :correo";
        $params = [':correo' => $_POST["correo"]];
        $arrayComprobar = $conexion->consultar($query, $params);

        $mensaje = ""; // mensaje que mandara al ajax

        // Verificar si hay resultados
        if (!empty($arrayComprobar)) {
            $pass = $arrayComprobar[0]["contrasena"];
            $email = $arrayComprobar[0]["correo"];

            // Verificar la contraseña
            if (password_verify($_POST["pass"], $pass) && ($_POST["correo"] == $email)) {

                $mensaje = "Credenciales correctas";


                $pin = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT); // crea 6 digitos aleatorios para el pin

                //enviara por correo (php mailer)
                $mensajeCorreo = "<html><head></head><body>
                                <h2>Verificación de Doble Autenticación</h2>
                                <p>Hola," . $arrayComprobar[0]["nombre"] . "</p>
                                <p>Estamos verificando tu identidad. Usa el siguiente código para completar el proceso de autenticación:</p>
                                <h3><strong>$pin</strong></h3>
                                <p>Este código es válido por 5 minutos. Si no solicitaste esta verificación, por favor ignora este mensaje.</p>
                                <p>Si no reconoces esta solicitud, no hagas nada. El código no tendrá ningún efecto y tu cuenta no se verá afectada.</p>
                                <p>Gracias por usar nuestros servicios.</p>
                                <p>Saludos,El equipo de AsisFin</p>
                                </body> </html> ";
                $envioCorreo = enviarCorreo($_POST["correo"], $mensajeCorreo, "Código de Autenticación - Doble Factor");

                if ($envioCorreo === 'Correo enviado con éxito') {
                    // crear session para almacenar el pin
                    $_SESSION['pin_2fa'] = password_hash($pin, PASSWORD_BCRYPT);
                    $_SESSION['pin_2fa_tiempo'] = time();
                    $_SESSION['pin_2fa_intentos'] = 0;
                }

                $_SESSION["idUsuario"] = $arrayComprobar[0]["id"];

                echo json_encode(['status' => 'success', 'mensaje' => 'Credenciales correctas']);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'mensaje' => 'Credenciales incorrectas']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'No existe el usuario']);
            exit;
        }


        break;

    case "Pin":

        // Verificar que no haya expirado (5 minutos)
        if (!isset($_SESSION['pin_2fa_tiempo']) || time() - $_SESSION['pin_2fa_tiempo'] > 300) {
            echo json_encode(['status' => 'error', 'mensaje' => 'PIN expirado']);
            exit;
        }

        // Verificar intentos máximos (3)
        if ($_SESSION['pin_2fa_intentos'] >= 3) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Demasiados intentos fallidos']);
            exit;
        }

        // Incrementar contador de intentos
        $_SESSION['pin_2fa_intentos']++;

        $pinIngresado = trim($_POST["pin"]);

        // Verificar PIN
        if (password_verify($pinIngresado, $_SESSION['pin_2fa'])) {
            unset($_SESSION['pin_2fa']);
            unset($_SESSION['pin_2fa_tiempo']);
            unset($_SESSION['pin_2fa_intentos']);

            $userToken = $_SESSION["idUsuario"];

            generarToken($userToken);

            //insertar al usuario en el historial de las sesiones iniciadas
            $conexion->insertar("sesiones", ["idUsuario"], [$_SESSION["idUsuario"]]);

            echo json_encode(['status' => 'success', 'mensaje' => 'Ha iniciado sesion exitosamente']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'PIN incorrecto']);
            exit;
        }
        break;
}
