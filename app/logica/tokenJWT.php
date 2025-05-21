<?php

// Instalar libreria: composer require firebase/php-jwt
include_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/../config/claves.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

cargarEnv();

// Configuración de seguridad para la sesión
function configurarSesion() {
    // Verificar si la sesión está activa y cerrarla si es necesario
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Configurar parámetros de seguridad de sesión ANTES de iniciar la sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_only_cookies', 1);
    
    // Iniciar sesión
    session_start();
}

// Generar token JWT cuando el usuario inicia sesión
function generarToken($user) {
   configurarSesion();
   
   $secret_key = $_ENV['TOKEN_KEY'];
   $issued_at = time();
   $expiration_time = $issued_at + 3600;  // 1 hora
   
   // Crear payload
   $payload = array(
       "iat" => $issued_at,
       "exp" => $expiration_time,
       "username" => $user
   );
   
   try {
       // Generar token JWT
       $jwt = JWT::encode($payload, $secret_key, 'HS256');
       
       // IMPORTANTE: Almacenar el token SOLO en la sesión del servidor
       $_SESSION['auth_token'] = $jwt;
       $_SESSION['auth_status'] = true;
       $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
       $_SESSION['username'] = $user;
       $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
       
       // Regenerar ID de sesión para prevenir ataques de fijación
       session_regenerate_id(true);
       
       return json_encode(['success' => 'true', 'message' => 'Autenticación exitosa.']);
   } catch (Exception $e) {
       return json_encode(['success' => 'false', 'message' => 'Error en autenticación', 'error' => $e->getMessage()]);
   }
}

// Validar token JWT
function validarToken() {
   configurarSesion();
   $secret_key = $_ENV['TOKEN_KEY'];
   
   // Verificar si existe el token en la sesión
   if (!isset($_SESSION['auth_token'])) {
       return json_encode(['success' => false, 'message' => 'false', 'error' => 'No hay sesión activa']);
   }
   
   $jwt = $_SESSION['auth_token'];
   
   try {
       // Decodificar y validar el token
       $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
       
       // Verificaciones adicionales de seguridad
       if ($_SESSION['username'] !== $decoded->username) {
           return json_encode(['success' => 'false', 'message' => 'false', 'error' => 'Inconsistencia de identidad detectada']);
       }
       
       if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
           return json_encode(['success' => 'false', 'message' => 'false', 'error' => 'Cambio en el navegador detectado']);
       }
       
       if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
           return json_encode(['success' => 'false', 'message' => 'false', 'error' => 'Cambio de dirección IP detectado']);
       }
       
       
       return json_encode(['success' => 'true', 'message' => 'true']);
  
   } catch (Exception $e) {
       // Si hay error, limpiar datos de autenticación
       session_unset();
       session_destroy();
       
       return json_encode([
           'success' => false, 
           'message' => 'false', 
           'error' => $e->getMessage()
       ]);
   }
}

// Cerrar sesión
function cerrarSesion() {
   configurarSesion();
   
   // Destruir sesión completamente
   session_unset();
   session_destroy();
   
   return json_encode(['success' => 'true', 'message' => 'true']);
}
?>