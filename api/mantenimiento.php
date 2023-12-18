<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('__ROOT__', str_replace('\\','/',dirname(dirname(__FILE__)))."/");
require_once (__ROOT__.'api/class/mantenimiento.class.php');

$_mante = new mantenimiento;    
error_log("Mantenimiento 2");
$_mante->ejecuta_sp();

?>