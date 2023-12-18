<?php

if(!defined('__ROOT__')) {
    define('__ROOT__', dirname(dirname(__FILE__)));
}

include_once(__ROOT__.'/clss/PDO.class.php');
if($db = new DB()){
	$sql_sel_cli = "select * from api_dispositivo";
	$data_sel_cli = array(
				"del" => 0
					);

	$ret_clientes = $db->getByObject($sql_sel_cli, $data_sel_cli);
	$db->CloseConnection();
    var_dump($ret_clientes);
}else{
    echo "Eliminar";
}    