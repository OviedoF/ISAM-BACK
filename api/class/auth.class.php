<?php
require_once (__ROOT__.'api/class/conexion.php');
require_once (__ROOT__.'api/class/respuestas.class.php');


class auth extends DB{

    public function login($json){
        error_log("Login 4");
        $_respustas = new respuestas;
        $datos = json_decode($json,true);
        if(!isset($datos['id'])){
            //error con los campos
            error_log("vavlida info 4.1");
            parent::CloseConnection();
            return $_respustas->error_400();
        }else{
            error_log("vavlida info 4.2");
            $dispositivo = trim($datos['id']);
            $existe=$this->valida_dispositivo($dispositivo);
            if($existe){
                error_log("vavlida info 4.3");
                   $verificar=$this->insertarToken($dispositivo);
                   if($verificar){
                    error_log("vavlida info 4.4");
                         $result = $_respustas->response;
                         $result["result"] = array("token" => $verificar);
                         parent::CloseConnection();
                        return $result;
                    }else{
                        parent::CloseConnection();
                        return $_respustas->error_500("Error en intento de Generar Token, Contacte con Soporte");
                    }
            }else{
                parent::CloseConnection();
                return $_respustas->error_200("Dispositivo No Ingresado");
            }
        }
    }

    public function valida_token($json){
        error_log("valida_token 7.1");
        $_respustas = new respuestas;
        $datos = json_decode($json,true);
    
        if(!isset($datos['id']) && !isset($datos['$token'])){

            //error con los campos
            error_log("valida_token 7.2");
            parent::CloseConnection();
            return $_respustas->error_400();
        }else{
            error_log("valida_token 7.3");
            $dispositivo = trim($datos['id']);
            $token = trim($datos['token']);
            $existe=$this->existe_token($dispositivo,$token);
            if($existe){
                error_log("valida_token 7.4");
                $result = $_respustas->response;
                $result["result"] = array("token" => "Activo");
                parent::CloseConnection();
               return $result;   
            }else{
                error_log("valida_token 7.5");
                parent::CloseConnection();
                return $_respustas->error_200("Token Inactivo");
            }
        }
    }

    public function verifica_area($json){
        error_log("verifica_area 6.1");
        //echo $json;
        $_respustas = new respuestas;
        $datos = json_decode($json,true);
        $lineas=count($datos['Lecturas']);
        if(!isset($datos['Datos']['TotalLectura']) || !isset($datos['Datos']['CodEmpresa']) || !isset($datos['Datos']['CodInv']) || !isset($datos['Datos']['CodCapturador']) || !isset($datos['Datos']['Area']) || !isset($datos['Datos']['TotalLectura'])){
            parent::CloseConnection();
            error_log("verifica_area 6.2");
            return $_respustas->error_200("Json Incorrecto o Incompleto");
        }else{
            error_log("verifica_area 6.3");
            $dispositivo=$datos['Datos']['id'];
            $token=$datos['Datos']['token'];
            $res_token=$this->existe_token($dispositivo,$token);
            if($res_token){
                error_log("verifica_area 6.4");
                $res=$this->valida_totales($datos['Lecturas'],$datos['Datos']['TotalLectura'],$lineas);
                if($res){
                    error_log("verifica_area 6.5");
                    $sql_res=$this->inserta_area($datos['Datos'],$datos['Lecturas'],$lineas,$token);
                    if($sql_res){
                        $respuesta_res=$this->CreaArchivo($datos['Datos'],$datos['Lecturas'],$lineas);
                        if($respuesta_res){
                            error_log("verifica_area 6.6");
                            $result = $_respustas->response;
                            $result["result"] = array('Carga y Respaldo Realizado con Exito');
                            parent::CloseConnection();
                            return $result;
                        }else{
                            error_log("verifica_area 6.7");
                            parent::CloseConnection();
                            return $_respustas->error_200("Error al Generar TXT");    
                        }
                    }else{
                        error_log("verifica_area 6.8");
                        $this->eliminar_carga($token,$dispositivo);
                        parent::CloseConnection();
                        return $_respustas->error_200("Error al Cargar los Datos en SQL");     
                    }    
                }else{
                    error_log("verifica_area 6.9");
                    parent::CloseConnection();
                    return $_respustas->error_200("Totales de Lectura y Cabecera no Corresponden");
                }
            }else{
                error_log("verifica_area 6.10");
                parent::CloseConnection();
                return $_respustas->error_200("Token Inactivo, Reintentar");
            }    
        }
    }
    private function existe_token($dispositivo,$token){
      error_log("Existe token 5.1");
      $query = "SELECT id_dispositivo as id 
                  FROM api_usuariotoken 
                  WHERE id_dispositivo = :id_dispositivo and token = :token and estado= :estado";
        
        $array_dis = array(
            "id_dispositivo" => $dispositivo,
            "token" => $token,
            "estado" => "Activo"
        );

        $datos = parent::getByObject($query, $array_dis);
        $res_boo=false;
        if(!empty($datos)){
            error_log("Existe token 5.1");
            $res=trim($datos[0]->id);
            if($res==$dispositivo){
                error_log("Existe token 5.2");
                $res_boo=true;
            }
            return $res_boo;
        }else{
            error_log("Existe token 5.3");
            return $res_boo;
        }
    }

    private function valida_dispositivo($dispositivo){
        error_log("Valida Dispositivo 4.1");
        $query = "SELECT id_dispositivo as id FROM api_dispositivo WHERE rtrim(ltrim(id_dispositivo)) = :id_dispositivo";
        $array_dis = array(            
            "id_dispositivo" => $dispositivo
        );


        $datos = parent::getByObject($query, $array_dis);
        $res_boo=false;
        error_log("Valida Dispositivo 4.2");
        if(!empty($datos)){
            error_log("Valida Dispositivo 4.3");
            $res=trim($datos[0]->id);
            if($res==$dispositivo){
                error_log("Valida Dispositivo 4.4");
                $res_boo=true;
            }
            return $res_boo;
        }else{
            error_log("Valida Dispositivo 4.5");
            return $res_boo;
        }
    }

    private function insertarToken($usuarioid){
        error_log("insertarToken 8.1");
        $val = true;
        $token = bin2hex(openssl_random_pseudo_bytes(16,$val));
        $date = date("Y-m-d H:i");
        $estado = "Activo";
        $query = "INSERT INTO api_usuariotoken (id_dispositivo,token,estado,fecha) VALUES (:id_dispositivo,:token,:estado,:fecha)";
        $params = array(
            "id_dispositivo" => $usuarioid,
            "token" => $token,
            "estado" => $estado,
            "fecha" => $date,
                );
        $verifica = parent::insertUpdateData($query, $params);
        if($verifica){
            error_log("insertarToken 8.2");
            return $token;
        }else{
            error_log("insertarToken 8.3");
            return 0;
        }
    }

    private function valida_totales($arreglo,$total,$lineas){
        error_log("valida_totales 9.1");
        $calculado=0;
        for($i=0;$i<$lineas;$i++){
            $calculado+=$arreglo[$i]['Cantidad'];
        }
        // error_log("Total enviado de Capturador: ".$total);
        // error_log("valida_totales: ".$calculado);
        // error_log("Cantiada de Lineas: ".$lineas);

        $res=false;
        // if($total==$calculado){
        if (bccomp($total, $calculado) == 0)
        {
            error_log("Valor Devuelto: Verdadero");
            error_log("valida_totales 9.2");
            $res=true;
        }
                
        return $res;
    }
   
    private function CreaArchivo($cabecera, $lecturas, $lineas)
    {
        error_log("CreaArchivo 10.1");
    
        $empresa = $cabecera['CodEmpresa'];
        $inventario = $cabecera['CodInv'];
        $fecha = str_replace('-', '_', substr($cabecera['FechaEnvio'], 0, 10));
        $hora = str_replace(':', '_', substr($cabecera['FechaEnvio'], 11, 8));
        $capturador = $cabecera['CodCapturador'];
        $area = substr($cabecera['Area'], 0, -1);
        $posicion = $cabecera['Posicion'];
        $caja = $cabecera['Caja'];
        $pallet = $cabecera['Pallet'];
    
        $nombre_archivo = $empresa . "_" . $inventario . "_" . $capturador . "_";
    
        if ($posicion !== "") {
            $nombre_archivo .= $posicion . "_";
        }

        if ($pallet !== "") {
            $nombre_archivo .= $pallet . "_";
        }

        if ($caja !== "") {
            $nombre_archivo .= $caja . "_";
        }

        if ($area !== "") {
            $nombre_archivo .= $area . "_";
        }
    
        $nombre_archivo .= $fecha . "_" . $hora . ".txt";
    
        // Crear la carpeta txt/$inventario si no existe
        if (!file_exists("txt/INV-" . $inventario)) {
            mkdir("txt/INV-" . $inventario, 0777, true);
        }
    
        // La ruta del archivo es txt/$inventario/empresa_inventario_capturador_area_fecha_hora.txt
        $fh = fopen("txt/INV-" . $inventario . "/" . $nombre_archivo, 'w') or die("Se produjo un error al crear el archivo");
    
        for ($i = 0; $i < $lineas; $i++) {
            $fecha_lectura = $this->fecha_cambio($lecturas[$i]['FechaLectura']);
            $lecturas[$i]['FechaLectura'] = str_replace('T', ' ', $fecha_lectura);
    
            $texto = $lecturas[$i]['CorrPt'] . "|" .
                $lecturas[$i]['FechaLectura'] . "|" .
                $capturador . "|" .
                $lecturas[$i]['CodOperador'] . "|";
                $texto .= $area . "|";
                $texto .= $posicion . "|";
                $texto .= $pallet . "|";
                $texto .= $caja . "|";

            if($lecturas[$i]['Serie'] !== ""){
                $texto .= $lecturas[$i]['Serie'] . "|";
            }

            $texto .= $lecturas[$i]['CodProducto'] . "|" .
                $lecturas[$i]['Cantidad'] . "|" .
                $lecturas[$i]['ExistenciaProducto'] . "|" .
                $lecturas[$i]['TipoLectura'] . "|" .
                trim($lecturas[$i]['EstadoTag']) . "|" .
                trim($lecturas[$i]['CorrelativoApertura']) . "\n";
    
            fwrite($fh, $texto) or die("No se pudo escribir en el archivo");
        }
    
        fclose($fh);
    
        return true;
    }

    
    private function fecha_cambio($fecha_json){ 
        error_log("fecha_cambio 11.1");
        // error_log("Fecha enviada de isam:".$fecha_json);
        $array_fecha=explode(' ',$fecha_json);
        // error_log(print_r($array_fecha,true));
        
        $array_date=explode('-',$array_fecha[0]);        
        $ano=substr('0000'.$array_date[0],-4);
        $mes=substr('0000'.$array_date[1],-2);
        $dia=substr('0000'.$array_date[2],-2);

        $array_time=explode(':',$array_fecha[1]);
        $hora=trim(substr('00'.$array_time[0],-2));
        $minuto=trim(substr('00'.$array_time[1],-2));
        $segundo=trim(substr('00'.$array_time[2],-2));
        error_log("**********2 Fecha ".$ano."-".$mes."-".$dia."T".$hora.":".$minuto.":".$segundo);
        return $ano."-".$mes."-".$dia."T".$hora.":".$minuto.":".$segundo;
    }

    // private function fecha_cambio($fecha_json){ 
    //     error_log("fecha_cambio 11.1");
        
    //     // * Cambiar el formato de la fecha de yyyy-mm-ddThh:mm:ss a yyyy-mm-dd hh:mm:ss

    //     $fecha=substr($fecha_json,0,10);
    //     $hora=substr($fecha_json,11,8);
    //     $fecha_cambio=$fecha." ".$hora;
    //     return $fecha_cambio;
    // }

    private function inserta_area($cabecera,$lecturas,$lineas,$token){
        error_log("inserta_area 12.1");

        // * Leemos todos los datos: $cabecera es el json que se mandó desde el front

        $area=trim($cabecera['Area']);
        $posicion=trim($cabecera['Posicion']);
        $caja=trim($cabecera['Caja']);
        $pallet=trim($cabecera['Pallet']);
        $capturador=trim($cabecera['CodCapturador']);
        $fecha_envio=$this->fecha_cambio(trim($cabecera['FechaEnvio']));
        $total_lectura=trim($cabecera['TotalLectura']);
        $dispositivo=trim($cabecera['id']);

        // echo $fecha_envio; 
        
        $empresa=$cabecera['CodEmpresa'];
        $inventario=$cabecera['CodInv'];
        
        $hora=str_replace(':','_',substr($cabecera['FechaEnvio'],11,8));
            
        // * Crear la tabla t_marca_inventario_($inventario) si no existe

        $table_exists_query = "SELECT COUNT(*) as table_exists FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 't_marca_inventario_tmp_".$inventario."'";
        $table_exists = parent::getByObject($table_exists_query, null)[0]->table_exists;
    
        if ($table_exists == 0) {
            // La tabla no existe, la creamos
            $sql_create_table = "CREATE TABLE t_marca_inventario_tmp_".$inventario." (
                id INT IDENTITY(1,1) NOT NULL,
                ID_INVENTARIO BIGINT NOT NULL,
                tag VARCHAR(100) NOT NULL,
                posicion VARCHAR(100) NOT NULL,
                caja VARCHAR(100) NOT NULL,
                pallet VARCHAR(100) NOT NULL,
                COD_CAPTURADOR VARCHAR(100) NOT NULL,
                fecha_envio DATETIME NOT NULL,
                total_tag FLOAT NOT NULL,
                cod_cap_ori VARCHAR(100) NOT NULL,
                CANTIDAD FLOAT NOT NULL,
                COD_OPERADOR VARCHAR(100) NOT NULL,
                COD_PRODUCTO VARCHAR(100) NOT NULL,
                CORR_PT VARCHAR(100) NOT NULL,
                EXISTENCIA_PRODUCTO VARCHAR(100) NOT NULL,
                serie VARCHAR(100) NOT NULL,
                TIPO_LECTURA VARCHAR(100) NOT NULL,
                est_tag VARCHAR(100) NOT NULL,
                corr_apertura VARCHAR(100) NOT NULL,
                FECHA_LECTURA DATETIME NOT NULL,
                deleted INT NOT NULL,
                PRIMARY KEY (id)
            )";
    
            $verifica = parent::insertUpdateData($sql_create_table, null);
        } // * Creación de la tabla t_marca_inventario_($inventario) si no existe
         
        $resultado=true;

        for($i=0;$i<$lineas;$i++){
            error_log("inserta_area 12.2");
            $cantidad=trim($lecturas[$i]['Cantidad']);
            $cod_operador=trim($lecturas[$i]['CodOperador']);
            $cod_producto=trim($lecturas[$i]['CodProducto']);
            $corr_pt=trim($lecturas[$i]['CorrPt']);
            $exis_prod=trim($lecturas[$i]['ExistenciaProducto']);
            $fecha_lectura=$this->fecha_cambio($lecturas[$i]['FechaLectura']);
            $serie=trim($lecturas[$i]['Serie']);
            $tipo_lectura=trim($lecturas[$i]['TipoLectura']);
            $est_tag=trim($lecturas[$i]['EstadoTag']);
            $corr_apertura=trim($lecturas[$i]['CorrelativoApertura']);    

            // * Insertar los datos en la tabla t_marca_inventario_($inventario)

            error_log("Cantidad leida".$cantidad);
            $sql_in="INSERT INTO t_marca_inventario_tmp_".$inventario." (ID_INVENTARIO, tag,COD_CAPTURADOR,fecha_envio,total_tag,cod_cap_ori,
                                                         CANTIDAD,COD_OPERADOR,COD_PRODUCTO,CORR_PT,EXISTENCIA_PRODUCTO,
                                                         serie,TIPO_LECTURA,est_tag,corr_apertura,FECHA_LECTURA, deleted, posicion, caja, pallet)
            VALUES (:id_inventario, :tag,:cod_capturador,:fecha_envio,:total_tag,:cod_cap_ori,
            :cantidad,:cod_operador,:cod_producto,:corr_pt,:existencia_producto,
            :serie,:tipo_lectura,:est_tag,:corr_apertura,:fecha_lectura,0, :posicion, :caja, :pallet)";
            error_log($sql_in);
            $params = array( 
                "id_inventario"=>$inventario,
                "tag"=>$area,
                "cod_capturador"=> $capturador,
                "fecha_envio"=>$fecha_envio,
                "total_tag"=> $total_lectura,
                "cod_cap_ori"=> $dispositivo,
                "cantidad"=> $cantidad,
                "cod_operador"=>$cod_operador,
                "cod_producto"=>$cod_producto,
                "corr_pt"=>$corr_pt,
                "existencia_producto"=>$exis_prod,
                "serie"=>$serie,
                "tipo_lectura"=>$tipo_lectura,
                "est_tag"=>$est_tag,
                "corr_apertura"=>$corr_apertura,
                "fecha_lectura"=>$fecha_lectura,
                "posicion"=>$posicion,
                "caja"=>$caja,
                "pallet"=>$pallet
            );

            // error_log(print_r($params,true));

            $verifica = parent::insertUpdateData($sql_in, $params);
            $resultado = $resultado && $verifica;
        }
        
        return $resultado;

    }

    private function eliminar_carga($token,$dispositivo){
        error_log("eliminar_carga 13.1");
        $sql_delete="delete from t_marca_inventario_tmp where token=:token and cod_capturador=':dispositivo'";
        $params = array(token=>$token,cod_dispositivo=>$dispositivo);
        
        $verifica = parent::insertUpdateData($query, $params);
    } 

    public function leerInventariosDisponibles(){
        $_respuestas = new respuestas;
        
        // Realiza la consulta para obtener los IDs de los inventarios disponibles
        $query = "SELECT id, description FROM dbo.inventarios WHERE deleted = 0";
        
        try {
            $datos = $this->getByObject($query, null);
            
            if (!empty($datos)) {
                // Forma un array con los IDs de los inventarios disponibles
                $inventariosDisponibles = array();
                foreach ($datos as $inventario) {
                    $inventariosDisponibles[] = array(
                        "id" => $inventario->id,
                        "description" => $inventario->description
                    );
                }
    
                $result = $_respuestas->response;
                $result["result"] = $inventariosDisponibles;
                
                return $result;
            } else {
                return $_respuestas->error_200("No hay inventarios disponibles");
            }
        } catch (Exception $e) {
            return $_respuestas->error_500("Error en la consulta: " . $e->getMessage());
        }
    }

    public function descargarMaestroInventario($id) {
        $directorio = 'db_inventarios';
        $archivo = "Maestro_{$id}.db";
        $rutaArchivo = "{$directorio}/{$archivo}";

        // Verificar si la carpeta existe, si no, crearla
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
            $mensaje = "La carpeta '{$directorio}' no existía, pero se creó.";
            return array("error" => true, "mensaje" => $mensaje);
        }

        // Verificar si el archivo existe
        if (file_exists($rutaArchivo)) {
            // Configurar las cabeceras para la descarga
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($rutaArchivo) . '"');
            header('Content-Length: ' . filesize($rutaArchivo));

            // Leer y enviar el archivo al navegador
            readfile($rutaArchivo);
            exit();
        } else {
            $mensaje = "El archivo '{$archivo}' no existe.";
            return array("error" => true, "mensaje" => $mensaje);
        }
    }
}
?>