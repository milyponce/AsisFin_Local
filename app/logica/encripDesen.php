<?php
include_once __DIR__ . '/../config/claves.php';
// Carga las variables de entorno definidas (como la clave de encriptación)
cargarEnv();

// Función para encriptar una cadena 
function encriptar($cadena){
    // Genera un IV (vector de inicialización) aleatorio del tamaño adecuado para AES-256-CBC
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // Encripta el NIF usando AES-256-CBC con la clave de entorno y el IV generado
    $cifrado = openssl_encrypt($cadena, 'aes-256-cbc', $_ENV['ENCRIP_DESEN_KEY'], 0, $iv);

    // Devuelve el texto cifrado junto con el IV codificado en base64 y separados por "::"
    return base64_encode($cifrado . '::' . base64_encode($iv));
}

// Función para desencriptar una cadena cifrado
function desencriptar($nifCifrado)
{
    // Decodifica la cadena en base64
    $decoded = base64_decode($nifCifrado);

    // Comprueba si el formato es válido (debe contener "::")
    if (!$decoded || strpos($decoded, '::') === false) {
        error_log("Error en formato de cifrado: " . $nifCifrado);
        return false;
    }

    // Separa el cifrado y el IV usando "::" como delimitador
    $datos = explode('::', $decoded, 2);

    // Comprueba que se hayan obtenido dos partes (cifrado e IV)
    if (count($datos) !== 2) {
        error_log("Error: no se pudo separar cifrado e IV.");
        return false;
    }

    // Asigna las dos partes a variables
    list($cifrado, $iv) = $datos;

    // Decodifica el IV (que también estaba en base64)
    $iv = base64_decode($iv);

    // Intenta desencriptar el texto usando la misma clave y IV
    $resultado = openssl_decrypt($cifrado, 'aes-256-cbc', $_ENV['ENCRIP_DESEN_KEY'], 0, $iv);

    // Si la desencriptación falla, se registra el error
    if ($resultado === false) {
        error_log("Error al desencriptar.");
    }

    // Devuelve el resultado desencriptado (o false si hubo error)
    return $resultado;
}
