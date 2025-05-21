<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!isset($_POST['pag'])) {
    exit(json_encode(['error' => 'Parámetro requerido no encontrado']));
}


switch ($_POST['pag']) {
    case 'hacerTransferencia':
        include_once  __DIR__ . '/../../app/logica/hacerTrasferencia.php';
        break;

    case 'crearCuenta':
        include_once  __DIR__ . '/../../app/logica/CrearCuenta.php';
        break;

    case 'inicioSesion':
        include_once  __DIR__ . '/../../app/logica/inicioSesion.php';
        break;

    case 'perfilUsuario':
        include_once  __DIR__ . '/../../app/logica/perfilUsuario.php';
        break;

    case 'presupuesto':
        include_once  __DIR__ . '/../../app/logica/presupuesto.php';
        break;

    case 'transacciones':
        include_once  __DIR__ . '/../../app/logica/transacciones.php';
        break;

    case 'recomendaciones':
        include_once  __DIR__ . '/../../app/logica/recomendaciones.php';
        break;

    case 'indexPHP':
        include_once  __DIR__ . '/../../app/logica/indexPHP.php';
        break;

    case 'foro':
        include_once  __DIR__ . '/../../app/logica/foro.php';
        break;

    default:
        echo json_encode([
            'error' => 'Ruta no encontrada'
        ]);
        break;
}
