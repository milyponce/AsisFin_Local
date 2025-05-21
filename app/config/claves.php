<?php
function cargarEnv() {
    // Cambiar la ruta para que busque en el directorio actual
    $rutaEnv = __DIR__ . '/.env';
    
    if (!file_exists($rutaEnv)) {
        throw new Exception("Archivo .env no encontrado en: " . $rutaEnv);
    }

    $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lineas as $linea) {
        if (strpos($linea, '=') !== false && strpos($linea, '#') !== 0) {
            list($clave, $valor) = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);
            
            putenv("$clave=$valor");
            $_ENV[$clave] = $valor;
        }
    }
}
