<?php
include_once __DIR__ . "/../config/Database.php";
include_once __DIR__ . "/../config/claves.php";
include_once __DIR__ . "/tokenJWT.php";

$conexion = new Database();
$conexion->obtenerConexion();
configurarSesion();


$idUsuario = $_SESSION['idUsuario'];
$fecha_actual = date('Y-m-d');
$arrayRecomen = $conexion->consultar("SELECT * FROM recomendaciones_personalizadas WHERE id_usuario = :id", [':id' =>  $idUsuario]);
$recomeAPI = "";

if (empty($arrayRecomen)) { //si no hay recomendaciones para el usuario crea una nueva
    $recomeAPI = API($conexion, $idUsuario);
    $conexion->insertar("recomendaciones_personalizadas", ["id_usuario", "recomendacion", "fecha_Creacion"], [$idUsuario, $recomeAPI, $fecha_actual]);
} else { //si ya hay recomendaciones para el usuario verifica si ya pasaron 15 dias para actualizarlas
    $fecha_creacion = $arrayRecomen[0]["fecha_creacion"];
    $fecha_creacion = date('Y-m-d', strtotime($fecha_creacion));
    $diferencia = abs(strtotime($fecha_actual) - strtotime($fecha_creacion));
    $dias = floor($diferencia / (60 * 60 * 24));

    if ($dias > 15) {
        $recomeAPI = API($conexion, $idUsuario);
        $conexion->ejecutar(
            "UPDATE recomendaciones_personalizadas SET  recomendacion = :recomendaciones, fecha_creacion=: fecha WHERE id_usuario=:idUsua",
            [":recomendaciones" => $recomeAPI, ":fecha" => $fecha_actual, ":idUsua" => $idUsuario]
        );
    } else {
        $recomeAPI = $arrayRecomen[0]["recomendacion"];
    }
}

echo $recomeAPI;

function API($conexion, $idUsuario)
{
    cargarEnv();

    $query = "SELECT  c.nombre AS nombre_categoria,   pc.monto AS presupuesto_monto, 
    IFNULL(SUM(CASE WHEN t.tipo = 'gasto' THEN t.monto ELSE 0 END), 0) AS monto_gastado, 
    p.id AS id_presupuesto FROM presupuesto p  JOIN presupuesto_categorias pc ON p.id = pc.idPresupuesto
    JOIN categorias c ON pc.idCategoria = c.id  LEFT JOIN transaccion t ON pc.idCategoria = t.idCategoria 
    AND t.idUsuario = p.idUsuario  WHERE p.idUsuario = :idUsuario GROUP BY c.nombre, pc.monto, p.id";
    $params = [':idUsuario' => $idUsuario];
    $presupuesto = $conexion->consultar($query, $params);

    $transacciones = $conexion->consultar(
        "SELECT t.*, IFNULL(t.descripcion, c.nombre) AS descripcion
        FROM transaccion t LEFT JOIN categorias c ON t.idCategoria = c.id
        WHERE t.idUsuario = :idUsuario",
        ["idUsuario" => $idUsuario]
    );

    

    if (empty($presupuesto) && empty($transacciones)) {
        $mensaje = "Dame una lista de recomendaciones generales de ahorro. La respuesta debe estar en formato HTML 
        usando etiquetas <ul> y <li>, y debes asegurarte de escapar o evitar caracteres especiales que puedan 
        romper el HTML o causar problemas al guardarlo en una base de datos. El contenido debe ser seguro, legible 
        y listo para ser insertado directamente en una página web. No incluyas código JavaScript ni etiquetas que
        no sean de lista. Responde solo con la lista nada mas.";
    } else {
        $mensaje = "Presupuesto:" . json_encode($presupuesto) . "Transacciones:" . json_encode($transacciones) .
            "Dame una lista de recomendaciones personalizadas basadas en el presupuesto disponible y las transacciones 
        recientes del usuario. La respuesta debe estar en formato HTML usando etiquetas <ul> y <li>, y debes asegurarte 
        de escapar o evitar caracteres especiales que puedan romper el HTML o causar problemas al guardarlo en una base de 
        datos. El contenido debe ser seguro, legible y listo para ser insertado directamente en una página web. No incluyas 
        código JavaScript ni etiquetas que no sean de lista. Responde solo con la lista nada mas.";
    }

    $url = "https://openrouter.ai/api/v1/chat/completions";
    $apiKey =  $_ENV['API_OPEN_ROUTER_KEY'];

    $data = json_encode([
        "model" => "mistralai/mistral-7b-instruct",
        "messages" => [
            ["role" => "system", "content" => "Eres un asesor financiero en España. Habla con el usuario y dale recomendaciones 
            personalizadas para mejorar su situación financiera.Respondes en Español"],
            ["role" => "user", "content" => $mensaje]
        ]
    ]);

    $headers = [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);

    return $responseData["choices"][0]["message"]["content"];;
}
