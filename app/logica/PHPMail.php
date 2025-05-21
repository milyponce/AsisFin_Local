<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include_once __DIR__ . '/../config/claves.php';
cargarEnv();

// Usar rutas absolutas para PHPMailer
require __DIR__ . "/../../lib/PHPMailer/src/Exception.php";
require __DIR__ . "/../../lib/PHPMailer/src/PHPMailer.php";
require __DIR__ . "/../../lib/PHPMailer/src/SMTP.php";

// Función para configurar PHPMailer
function configurarCorreo() {
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPDebug = 0; // 0 para no mostrar errores, 1 para información básica, 2 para detalles avanzados
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'TLS';
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->Timeout = 30;

    //// Opciones para resolver problemas de certificados
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Configuración de usuario
    $mail->Username = "asis.fin.pers@gmail.com"; // Usuario de Google 
    $mail->Password = $_ENV['KEY_CORREO'] ; // Clave 
    $mail->SetFrom('asis.fin.pers@gmail.com', 'AsisFin'); // Mail y nombre de remitente
    return $mail;
}


// Función para enviar el correo
function enviarCorreo($correoDestinatario, $mensaje,$asunto) {
    try {
        // Configuración del correo
        $mail = configurarCorreo();

        // Configuración del contenido del correo
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto; // Asunto del correo
        $mail->MsgHTML($mensaje); // Mensaje HTML

        // Dirección de destino
        $mail->AddAddress($correoDestinatario, "Destinatario");

        // Enviar el correo
        if ($mail->Send()) {
            return 'Correo enviado con éxito';
        } else {
            return 'Error: ' . $mail->ErrorInfo;
        }
    } catch (Exception $ex) {
        return 'Error detectado: ' . $ex->getMessage();
    }
}

?>
