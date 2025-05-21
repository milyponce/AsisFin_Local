<?php

// Corregir la ruta de inclusión según tu estructura
include_once __DIR__ . '/../config/claves.php';

function handleError($message, $code = 500)
{
    echo json_encode([
        'status' => 'error',
        'mensaje' => $message
    ]);
    exit;
}

// Capturar errores fatales que puedan ocurrir
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'mensaje' => 'Error fatal: ' . $error['message']
        ]);
    }
});

cargarEnv();

// Clase para manejar la conexión
class ConexionBD
{
    private $pdo;

    public function __construct()
    {
        $host = $_ENV['HOST_BD_API'];
        $user = $_ENV['USER_BD_API'];
        $pass = "";
        $db   = $_ENV['NAME_BD_API'];

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error de conexión: " . $e->getMessage());
        }
    }

    public function getConexion()
    {
        return $this->pdo;
    }
}

// Clase para el usuario
class Usuario
{
    private $db;

    public function __construct()
    {
        $conexion = new ConexionBD();
        $this->db = $conexion->getConexion();
    }

    public function obtenerUsuario($numero_documento)
    {
        try {
            // Modificamos la consulta para obtener todos los campos
            $stmt = $this->db->prepare("SELECT * FROM usuarios  WHERE numero_documento = ?");
            $stmt->execute([$numero_documento]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            // Log para debugging
            error_log('Resultado consulta: ' . print_r($resultado, true));

            if (!$resultado) {
                return [
                    'status' => 'error',
                    'mensaje' => 'Usuario no encontrado'
                ];
            }

            // Devolvemos todos los datos en la respuesta
            return [
                'status' => 'success',
                'data' => $resultado
            ];
        } catch (PDOException $e) {
            error_log("Error en obtenerUsuario: " . $e->getMessage());
            return [
                'status' => 'error',
                'mensaje' => 'Error al consultar la base de datos'
            ];
        }
    }
}

// Clase para las transferencias
class Transferencia
{
    private $db;

    public function __construct()
    {
        $conexion = new ConexionBD();
        $this->db = $conexion->getConexion();
    }

    //crea transferencia entre cuentas
    public function crearTransferencia($monto, $concepto, $cuenta_origen, $cuenta_destino)
    {
        if (
            !isset($cuenta_origen) || !isset($cuenta_destino) || is_null($cuenta_origen) || is_null($cuenta_destino)
        ) {
            return [
                'status' => 'error',
                'mensaje' => 'No se pudo acceder a los datos requeridos'
            ];
        }

        try {
            $this->db->beginTransaction();

            // Verificar si la cuenta de origen existe y obtener el saldo
            $stmtOrigen = $this->db->prepare("SELECT saldo FROM usuarios WHERE cuenta_bancaria = ?");
            $stmtOrigen->execute([$cuenta_origen]);
            $resultado = $stmtOrigen->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                $this->db->rollBack();
                return ['status' => 'error', 'mensaje' => 'Cuenta de origen no encontrada'];
            }

            if ($resultado['saldo'] < $monto) {
                $this->db->rollBack();
                return ['status' => 'error', 'mensaje' => 'Saldo insuficiente'];
            }

            // Verificar que la cuenta destino exista
            $stmtDestino = $this->db->prepare("SELECT 1 FROM usuarios WHERE cuenta_bancaria = ?");
            $stmtDestino->execute([$cuenta_destino]);
            if (!$stmtDestino->fetch()) {
                $this->db->rollBack();
                return ['status' => 'error', 'mensaje' => 'Cuenta de destino no encontrada'];
            }

            // Crear transferencia
            $stmt = $this->db->prepare("INSERT INTO transferencias (monto, concepto, cuenta_origen, cuenta_destino) VALUES (?, ?, ?, ?)");
            $stmt->execute([$monto, $concepto, $cuenta_origen, $cuenta_destino]);

            // Actualizar saldo de cuenta origen
            $stmtActualizarOrigen = $this->db->prepare("UPDATE usuarios SET saldo = saldo - ? WHERE cuenta_bancaria = ?");
            $stmtActualizarOrigen->execute([$monto, $cuenta_origen]);

            // Actualizar saldo de cuenta destino
            $stmtActualizarDestino = $this->db->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE cuenta_bancaria = ?");
            $stmtActualizarDestino->execute([$monto, $cuenta_destino]);

            $this->db->commit();
            return ['status' => 'success', 'mensaje' => 'Transferencia realizada con éxito'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en crearTransferencia: " . $e->getMessage());
            return ['status' => 'error', 'mensaje' => 'Error al procesar la transferencia'];
        }
    }


    // ver transferencias del usuario
    public function obtenerTransferencias($cuenta_bancaria, $filtro, $orden)
    {
        // Validación inicial
        if (!isset($cuenta_bancaria) || is_null($cuenta_bancaria)) {
            return ['status' => 'error', 'mensaje' => 'No se pudo acceder a los datos requeridos'];
        }

        // Validar que $orden sea 'ASC' o 'DESC'
        $orden = strtoupper($orden);
        if ($orden !== 'ASC' && $orden !== 'DESC') {
            $orden = 'DESC'; // Valor por defecto
        }

        // Preparar la consulta según el filtro
        if ($filtro === '*') {
            // Todas las transferencias (enviadas o recibidas)
            $stmt = $this->db->prepare("SELECT * FROM transferencias WHERE cuenta_origen = ? OR cuenta_destino = ? ORDER BY fecha $orden");
            $stmt->execute([$cuenta_bancaria, $cuenta_bancaria]);
        } elseif ($filtro === "ingreso") {
            // Solo transferencias recibidas (ingresos)
            $stmt = $this->db->prepare("SELECT * FROM transferencias WHERE cuenta_destino = ? ORDER BY fecha $orden");
            $stmt->execute([$cuenta_bancaria]);
        } elseif ($filtro === "gasto") {
            // Solo transferencias enviadas (gastos)
            $stmt = $this->db->prepare("SELECT * FROM transferencias WHERE cuenta_origen = ? ORDER BY fecha $orden");
            $stmt->execute([$cuenta_bancaria]);
        } else {
            // Filtro por concepto específico
            $stmt = $this->db->prepare("SELECT * FROM transferencias WHERE (cuenta_origen = ? OR cuenta_destino = ?) AND concepto = ? ORDER BY fecha $orden");
            $stmt->execute([$cuenta_bancaria, $cuenta_bancaria, $filtro]);
        }

        // Obtener y devolver los resultados
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'status' => 'success',
            'mensaje' => $resultados
        ];
    }
}


try {
    // Recibir datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }

    // Incluir las clases necesarias
    include_once __DIR__ . '/../config/claves.php';

    // Determinar qué acción realizar basado en los datos recibidos
    $accion = $data['accion'] ?? '';

    switch ($accion) {
        case 'obtenerUsuario':
            $usuario = new Usuario();
            $resultado = $usuario->obtenerUsuario($data['numero_documento'] ?? '');
            echo json_encode($resultado);
            break;

        case 'crearTransferencia':
            $transferencia = new Transferencia();
            $resultado = $transferencia->crearTransferencia(
                $data['monto'] ?? 0,
                $data['concepto'] ?? '',
                $data['cuenta_origen'] ?? '',
                $data['cuenta_destino'] ?? ''
            );
            echo json_encode($resultado);
            break;

        case 'obtenerTransferencias':
            $transferencia = new Transferencia();
            $resultado = $transferencia->obtenerTransferencias(
                $data['cuenta_bancaria'] ?? '',
                $data['filtro'] ?? '*',
                $data['orden'] ?? 'DESC'
            );
            echo json_encode($resultado);
            break;

        default:
            throw new Exception('Acción no reconocida');
    }
} catch (Exception $e) {
    handleError($e->getMessage());
}
