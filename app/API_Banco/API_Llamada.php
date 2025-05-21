<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

class API_Llamada {
    private $baseUrl;
    
    public function __construct($baseUrl = null) {
        if ($baseUrl === null) {
            $this->baseUrl = 'http://localhost/PROYECTO/Asisfin%20Local/app/API_Banco/api.php';
        } else {
            $this->baseUrl = $baseUrl;
        }
    }
    
    public function llamarAPI($datos) {
        try {
            if (empty($datos)) {
                throw new Exception('Datos vacíos');
            }
            
            $jsonData = json_encode($datos);
            if ($jsonData === false) {
                throw new Exception('Error codificando datos: ' . json_last_error_msg());
            }
            
            $ch = curl_init($this->baseUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);
            
            // Capturar respuesta y errores
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Mejor logging para depuración
            error_log("URL llamada: " . $this->baseUrl);
            error_log("Datos enviados: " . $jsonData);
            error_log("Código HTTP: " . $httpCode);
            error_log("Respuesta recibida (raw): " . $response);
            
            if ($response === false) {
                throw new Exception("Error CURL: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            // Verifica si la respuesta está vacía
            if (empty($response)) {
                throw new Exception("La respuesta del API está vacía");
            }
            
            // Verifica si el response es JSON válido antes de decodificar
            $decodedResponse = json_decode($response, true);
            if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
                // Mostrar más información sobre el error y los primeros 1000 caracteres de la respuesta
                $errorMsg = "Error decodificando respuesta JSON: " . json_last_error_msg();
                $responsePreview = strlen($response) > 1000 ? substr($response, 0, 1000) . "..." : $response;
                throw new Exception($errorMsg . ". Respuesta recibida: " . $responsePreview);
            }
            
            return $decodedResponse;
            
        } catch (Exception $e) {
            error_log("Error en API_Llamada: " . $e->getMessage());
            return [
                'status' => 'error',
                'mensaje' => "Error en la comunicación con el banco: " . $e->getMessage(),
                'debug_info' => [
                    'url' => $this->baseUrl,
                    'datos_enviados' => $datos,
                    'respuesta' => isset($response) ? substr($response, 0, 1000) : null,
                    'http_code' => $httpCode ?? null
                ]
            ];
        }
    }
}