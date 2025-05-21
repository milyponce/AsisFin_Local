<?php
include_once __DIR__ ."/claves.php"; // Solo una vez

// Llamar a la función para cargar las variables de entorno
cargarEnv();

class Database
{
    private $host;
    private $usuario;
    private $contraseña;
    private $basedatos;
    private $conexion;

    // Constructor de la clase
    public function __construct()
    {
        // Asigna las variables de entorno a las propiedades de la clase
        $this->host = $_ENV['HOST_BD'];         // Dirección del servidor
        $this->usuario = $_ENV['USER_BD'];      // Usuario de la base de datos
        $this->contraseña = ""; // Contraseña del usuario
        $this->basedatos =  $_ENV['NAME_BD'];    // Nombre de la base de datos
        $this->conectar(); // Llamar a la conexión al instante
    }

    // Método para establecer la conexión a la base de datos
    private function conectar()
    {
        try {
            // Creamos la conexión usando PDO y configuramos la codificación a UTF-8
            $this->conexion = new PDO("mysql:host=$this->host;dbname=$this->basedatos", $this->usuario, $this->contraseña, [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4" // Configura la codificación UTF-8
            ]);

            // Configuramos el manejo de errores en PDO (Excepciones)
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Si hay un error de conexión, se lanza una excepción
            echo "Error de conexión: " . $e->getMessage();
            die(); // Considera manejar el error de una forma más amigable para producción
        }
    }


    // Método para obtener la conexión
    public function obtenerConexion()
    {
        return $this->conexion;
    }
    // Método para realizar consultas SELECT (retorna resultados)
    public function consultar($query, $params = [])
    {
        if (!is_array($params)) {
            $params = [];
        }
        $stmt = $this->conexion->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decodificar las respuestas para mostrar los caracteres correctamente
        foreach ($results as $key => $value) {
            // Decodificar los valores de todas las columnas que contengan texto
            $results[$key] = array_map(function ($item) {
                if ($item === null) {
                    return '';
                }
                return html_entity_decode((string)$item, ENT_QUOTES, 'UTF-8');
            }, $value);
        }

        return $results;
    }

    public function consultarSinParams($query)
    {
        $stmt = $this->conexion->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decodificar las respuestas para mostrar los caracteres correctamente
        foreach ($results as $key => $value) {
            // Decodificar los valores de todas las columnas que contengan texto
            $results[$key] = array_map(function ($item) {
                if ($item === null) {
                    return '';
                }
                return html_entity_decode((string)$item, ENT_QUOTES, 'UTF-8');
            }, $value);
        }

        return $results;
    }

    // Método para ejecutar una consulta  DELETE, UPDATE
    public function ejecutar($query, $params = [])
    {
        $stmt = $this->conexion->prepare($query);
        return $stmt->execute($params); // Ejecutamos con parámetros  
    }


    public function insertar($tabla, $campos, $valores)
    {
        // Asegúrate de que el número de campos y valores coincidan
        if (count($campos) != count($valores)) {
            throw new Exception("El número de campos no coincide con el número de valores.");
        }

        // Construir la consulta SQL dinámicamente
        $columnas = implode(", ", $campos);
        $placeholders = ":" . implode(", :", $campos);  // Crea los placeholders como :campo1, :campo2, etc.

        // Consulta SQL para insertar datos (sin modificar 'iban' por ahora)
        $query = "INSERT INTO $tabla ($columnas) VALUES ($placeholders)";

        // Preparar la sentencia
        $stmt = $this->conexion->prepare($query);

        // Asociar los valores con los placeholders
        foreach ($campos as $index => $campo) {
            // Si es 'contrasena', la hasheamos antes de insertarla
            if ($campo === 'contrasena') {
                $valores[$index] = password_hash($valores[$index], PASSWORD_DEFAULT);
            }
            // Para otros campos, asignamos el valor tal cual
            $stmt->bindParam(":$campo", $valores[$index], PDO::PARAM_STR);
        }

        // Ejecutar la consulta de inserción
        $stmt->execute();

        return true;
    }



    //Obtiene le ultimo registro ingresado
    public function ultimoIdInsertado()
    {
        return $this->conexion->lastInsertId();
    }

    // Método para cerrar la conexión
    public function cerrar()
    {
        $this->conexion = null;
    }
}
