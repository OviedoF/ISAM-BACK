<?php

require_once (__ROOT__.'api/class/conexion.php');

class mantenimiento extends DB{

    public function ejecuta_sp(){
        $query_sp ="EXEC dbo.pa_mantenimiento_token";

        $array_sp = array();
        $res=parent::insertUpdateData($query_sp, $array_sp);
        echo $res;
    }
}

?>