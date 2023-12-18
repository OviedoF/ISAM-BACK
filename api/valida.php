<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('__ROOT__', str_replace('\\','/',dirname(dirname(__FILE__)))."/");

require_once (__ROOT__.'api/class/auth.class.php');
require_once (__ROOT__.'api/class/respuestas.class.php');

$_auth = new auth;
$_respuestas = new respuestas;
error_log("valida info 3");
if($_SERVER['REQUEST_METHOD'] == "POST"){

    error_log("valida info 3.1");
    //recibir datos
    $postBody = file_get_contents("php://input");

    //enviamos los datos al manejador
    $datosArray = $_auth->valida_token($postBody);

    //delvolvemos una respuesta
    header('Content-Type: application/json');
    if(isset($datosArray["result"]["error_id"])){
        error_log("valida info 3.2");
        $responseCode = $datosArray["result"]["error_id"];
        http_response_code($responseCode);
    }else{
        error_log("valida info 3.3");
        http_response_code(200);
    }
    echo json_encode($datosArray);
    error_log("valida info 3.4");


}else{
    error_log("valida info 3.5");
    header('Content-Type: application/json');
    $datosArray = $_respuestas->error_405();
    echo json_encode($datosArray);

}
?>