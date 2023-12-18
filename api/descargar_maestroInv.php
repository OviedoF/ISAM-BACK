<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('__ROOT__', str_replace('\\', '/', dirname(dirname(__FILE__))) . "/");

require_once(__ROOT__ . '/api/class/auth.class.php');
require_once(__ROOT__ . '/api/class/respuestas.class.php');

$_auth = new auth;
$_respuestas = new respuestas;

try {
    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        // Obtener el valor de :id desde el encabezado HTTP
        $id = isset($_SERVER['HTTP_ID']) ? $_SERVER['HTTP_ID'] : null;

        // Verificar si se proporcionó el parámetro :id
        if ($id === null) {
            header('Content-Type: application/json');
            $errorArray = $_respuestas->error_400("Parámetro :id no proporcionado en el encabezado HTTP.");
            http_response_code(400);
            echo json_encode($errorArray);
            exit;
        }

        // Llamar a la función con el valor de :id
        $datosArray = $_auth->descargarMaestroInventario($id);

        header('Content-Type: application/json');
        if (isset($datosArray["result"]["error_id"])) {
            $responseCode = $datosArray["result"]["error_id"];
            http_response_code($responseCode);
        } else {
            http_response_code(200);
        }
        echo json_encode($datosArray);
    } else {
        header('Content-Type: application/json');
        $datosArray = $_respuestas->error_405();
        echo json_encode($datosArray);
    }
} catch (Exception $e) {
    // Manejar cualquier excepción global aquí
    header('Content-Type: application/json');
    $errorArray = $_respuestas->error_500("Error inesperado: " . $e->getMessage());
    http_response_code(500);
    echo json_encode($errorArray);
}
?>