<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once  __DIR__ . "/../config/Database.php";
include_once __DIR__ . "/tokenJWT.php";

$conexion = new Database();
$conexion->obtenerConexion();

if (!$conexion) {
    die("Error de conexión a la base de datos.");
}

configurarSesion();

switch ($_POST["accion"]) {
    case "categorias":
        $arrayCategorias = $conexion->consultarSinParams("SELECT * FROM categorias");
        echo json_encode($arrayCategorias);
        break;

    case "guardar":
        // Consultar si el usuario ya tiene un presupuesto
        $presupuesto = $conexion->consultar(
            "SELECT id FROM presupuesto WHERE idUsuario = :idUsuario",
            ["idUsuario" => $_SESSION['idUsuario']]
        );

        // Si no existe, insertarlo y obtener el nuevo ID
        if (empty($presupuesto)) {
            $conexion->insertar("presupuesto", ["idUsuario"], [$_SESSION['idUsuario']]);

            // Obtener el ID recién insertado
            $presupuesto_id = $conexion->ultimoIdInsertado();
        } else {
            // Si ya existe, obtener el ID existente
            $presupuesto_id = $presupuesto[0]['id'];
        }

        // Verificar si ya existe la categoría en este presupuesto
        $categoria_existente = $conexion->consultar(
            "SELECT * FROM presupuesto_categorias 
                WHERE idPresupuesto = :idPresupuesto AND 
                idCategoria = :idCategoria",
            [
                "idPresupuesto" => $presupuesto_id,
                "idCategoria" => $_POST["idCategoria"]
            ]
        );

        if (empty($categoria_existente)) {
            // Si no existe, insertar nuevo registro
            $conexion->insertar(
                "presupuesto_categorias",
                ["idPresupuesto", "idCategoria", "monto"],
                [$presupuesto_id, $_POST["idCategoria"], $_POST["monto"]]
            );
        } else {
            // Si ya existe, actualizar sumando el nuevo monto
            $nuevo_monto = $categoria_existente[0]['monto'] + $_POST["monto"];
            $conexion->ejecutar(
                "UPDATE presupuesto_categorias 
                    SET monto = :nuevo_monto 
                    WHERE idCategoria = :idCategoria AND idPresupuesto = :idPresupuesto",
                [
                    ":nuevo_monto" => $nuevo_monto,
                    ":idCategoria" => $_POST["idCategoria"],
                    ":idPresupuesto" => $presupuesto_id
                ]
            );
        }
        echo json_encode(["success" => true, "message" => "Presupuesto guardado correctamente"]);
        break;


    case "mostrar_datos_graficos":
        $query = "SELECT 
                c.nombre AS nombre_categoria, 
                pc.monto AS presupuesto_monto, 
                IFNULL(SUM(CASE WHEN t.tipo = 'gasto' THEN t.monto ELSE 0 END), 0) AS monto_gastado, 
                p.id AS id_presupuesto
              FROM presupuesto p
              JOIN presupuesto_categorias pc ON p.id = pc.idPresupuesto
              JOIN categorias c ON pc.idCategoria = c.id
              LEFT JOIN transaccion t ON pc.idCategoria = t.idCategoria AND t.idUsuario = p.idUsuario
              WHERE p.idUsuario = :idUsuario
              GROUP BY c.nombre, pc.monto, p.id";

        $params = [':idUsuario' => $_SESSION['idUsuario']];
        $arrayMostra = $conexion->consultar($query, $params);

        echo json_encode($arrayMostra);
        break;

    case "eliminar":
        // Eliminar las categorías asociadas a un presupuesto
        $query = "DELETE FROM presupuesto_categorias WHERE idPresupuesto = :id_presupuesto AND idCategoria = :id_categoria";
        $params = [
            ':id_presupuesto' => $_POST["id_presupuesto"],   // ID del presupuesto
            ':id_categoria' => $_POST["id_categoria"]   // ID de la categoría a eliminar
        ];
        $conexion->ejecutar($query, $params);
        echo json_encode(["success" => true, "message" => "Categoría eliminada correctamente"]);
        break;

    case "motrarModificacion":
        $query = "SELECT 
                c.id AS id_categoria, 
                c.nombre AS nombre_categoria, 
                pc.monto AS monto_presupuesto, 
                p.id AS id_presupuesto
              FROM presupuesto_categorias pc
              JOIN categorias c ON pc.idCategoria = c.id
              JOIN presupuesto p ON pc.idPresupuesto = p.id
              WHERE p.id = :id_modificar";

        $params = [':id_modificar' => $_POST["id_mostrarModificar"]];
        $resultado = $conexion->consultar($query, $params);
        echo json_encode($resultado);
        break;

    case "actualizarCategoria":
        $query = "UPDATE presupuesto_categorias 
              SET monto = :monto 
              WHERE idPresupuesto = :idPresupuesto 
              AND idCategoria = :idCategoria 
              AND idPresupuesto IN (SELECT id FROM presupuesto WHERE id = :idPresupuesto AND idUsuario = :idUsuario)";

        $params = [
            ':idPresupuesto' => $_POST["idPresupuesto"],  // ID del presupuesto
            ':idCategoria' => $_POST["idCategoria"],      // ID de la categoría
            ':monto' => $_POST["monto"],                  // Nuevo monto
            ':idUsuario' => $_SESSION['idUsuario']           // ID del usuario
        ];

        $conexion->ejecutar($query, $params);
        echo json_encode(["success" => true, "message" => "Categoría actualizada correctamente"]);
        break;

    case "mostrarTabla":
        $query = "SELECT 
                c.id AS id_categoria, 
                p.id AS id_presupuesto, 
                pc.monto, 
                c.nombre AS nombre_categoria
              FROM presupuesto_categorias pc
              JOIN categorias c ON pc.idCategoria = c.id
              JOIN presupuesto p ON pc.idPresupuesto = p.id
              WHERE p.idUsuario = :idUsuario";

        $params = [':idUsuario' => $_SESSION['idUsuario']];
        $resultado = $conexion->consultar($query, $params);

        echo json_encode($resultado);
        break;

    case "verificar_alertas":
        $query = "SELECT 
                c.nombre AS nombre_categoria,
                pc.monto AS presupuesto_monto,
                IFNULL(SUM(CASE WHEN t.tipo = 'gasto' THEN t.monto ELSE 0 END), 0) AS monto_gastado,
                (IFNULL(SUM(CASE WHEN t.tipo = 'gasto' THEN t.monto ELSE 0 END), 0) - pc.monto) AS exceso,
                ROUND(
                    (IFNULL(SUM(CASE WHEN t.tipo = 'gasto' THEN t.monto ELSE 0 END), 0) / pc.monto) * 100, 
                    2
                ) AS porcentaje_usado
            FROM presupuesto p
            JOIN presupuesto_categorias pc ON p.id = pc.idPresupuesto
            JOIN categorias c ON pc.idCategoria = c.id
            LEFT JOIN transaccion t ON pc.idCategoria = t.idCategoria AND t.idUsuario = p.idUsuario
            WHERE p.idUsuario = :idUsuario
            GROUP BY c.nombre, pc.monto
            HAVING monto_gastado > pc.monto
            ORDER BY exceso DESC";

        $params = [':idUsuario' => $_SESSION['idUsuario']];
        $categorias_excedidas = $conexion->consultar($query, $params);

        $alertas = [];
        foreach ($categorias_excedidas as $categoria) {
            $alertas[] = [
                'categoria' => $categoria['nombre_categoria'],
                'presupuesto' => number_format($categoria['presupuesto_monto'], 2),
                'gastado' => number_format($categoria['monto_gastado'], 2),
                'exceso' => number_format($categoria['exceso'], 2),
                'porcentaje' => $categoria['porcentaje_usado']
            ];
        }

        echo json_encode([
            'hay_alertas' => !empty($alertas),
            'alertas' => $alertas,
            'total_categorias_excedidas' => count($alertas)
        ]);
        break;

    default:
        echo json_encode(["error" => "Acción no reconocida"]);
        break;
}

