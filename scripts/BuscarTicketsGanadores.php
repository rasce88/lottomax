<?php
// Archivo de variables de configuracion
require_once('../config/config.php');
$obj_config= new ConfigVars();
// Archivo de mensajes
require_once('.'.$obj_config->GetVar('ruta_config').'mensajes.php');
// Clase Generica
require('.'.$obj_config->GetVar('ruta_libreria').'Generica.php');
$obj_generico= new Generica();
// Conexion a la bases de datos
require('.'.$obj_config->GetVar('ruta_libreria').'Bd.php');
$obj_conexion= new Bd();
if( !$obj_conexion->ConnectDataBase($obj_config->GetVar('host'), $obj_config->GetVar('data_base'), $obj_config->GetVar('usuario_db'), $obj_config->GetVar('clave_db')) ){
	echo "sin_conexion_bd";
}
// Modelo asignado
require('.'.$obj_config->GetVar('ruta_modelo').'Pagar_Ganador.php');
$obj_modelo= new Pagar_Ganador($obj_conexion);
$tipo_servidor=$obj_modelo->GetTipoServidor();
$id_detalle_ticket[]="";
$id_tickets[]="";
$totales[]="";
if(isset($_GET['fecha_desde']))
$fecha_desde=$_GET['fecha_desde'];
else
$fecha_desde=date('Y-m-d');
if(isset($_GET['fecha_hasta']))
$fecha_hasta=$_GET['fecha_hasta'];
else
$fecha_hasta=date('Y-m-d');
echo "URL ?fecha_desde=".$fecha_desde."&fecha_hasta=".$fecha_hasta."<br>";
$fecha_hora=$fecha_desde;
//exit;
/*echo "DESDE".strtotime($fecha_hora);
echo "Hasta".strtotime($fecha_hasta);
exit;
*/
$aprox= $obj_modelo->GetAprox();
$result=$obj_modelo->GetRelacionPagos();
$relacion_pago=array();
while($row=$obj_conexion->GetArrayInfo($result)){
	$relacion_pago[$row['id_tipo_jugada']]=$row['monto'];
	//	$id_tipo_jugada[]=$row['id_tipo_jugada'];
}
while(strtotime($fecha_hora)<=strtotime($fecha_hasta) ){
	//echo "fech.".$fecha_hora;
	$res=$obj_modelo->DespremiarTicket($fecha_hora,$tipo_servidor);
	//echo "<br>REST".$res;
	if($res)
	echo "<br>Despremiar Efectivo";
	else{
		echo "Fallo el despremiar. Intente Nuevamente";
		exit;	
	}
	//exit;
	//$where = " fecha_hora LIKE '%".date('Y-m-d')."%'";
	//$result= $obj_modelo->GetListadosegunVariable($where);
	$resultados=array();
	$id_sorteo=array();
	$id_zodiacal=array();
	$relacion_pago=array();
	$result=$obj_modelo->GetResultados($fecha_hora);
	while($row=$obj_conexion->GetArrayInfo($result)){
		$resultados[]=$row['numero'];
		$id_sorteo[]=$row['id_sorteo'];
		$id_zodiacal[]=$row['zodiacal'];
	}
	$fecha_actual=date('Y-m-d');
	$result=$obj_modelo->GetRelacionPagos($obj_conexion);
//	echo "Numero".$obj_conexion->GetNumberRows($result);
	while($row=$obj_conexion->GetArrayInfo($result)){
		/*echo "<br>Id_tipo ".$row['id_tipo_jugada'];
		echo "<br>Id_agencia ".$row['id_agencia'];
		echo "<br>Monto ".$row['monto'];*/
		$relacion_pago[$row['id_tipo_jugada']][$row['id_agencia']]=$row['monto'];
	}
	$result= $obj_modelo->GetListadosegunVariable2($fecha_hora,$obj_conexion,$fecha_actual,$tipo_servidor);
    If ($obj_conexion->GetNumberRows($result)>0){
    	for($i=0;$i<count($resultados);$i++){
	    	$ticket_premiado=0;
	    	$monto_total_ticket=0;
	    	while ($roww= $obj_conexion->GetArrayInfo($result)){
	    		if($tipo_servidor==1 OR $tipo_servidor==2)
	    		$id_ticket=$roww["id_ticket"];
	    		else	
	    		if($fecha_hora<$fecha_actual)
				$id_ticket=$roww["id_ticket"];
				else
				$id_ticket=$roww["id_ticket_diario"];
	    		//$id_ticket=$roww["id_ticket"];
	    		$fecha_ticket= substr($roww["fecha_hora"],0 , -9);
	    		$resultDT = $obj_modelo->GetAllDetalleTciket2($id_ticket);
		        //revisamos la tabla de detalle ticket y comparamos con los resultados
	    		$monto_total=$roww['total_premiado'];
	    		$sw=0;
	    	    while($rowDT= $obj_conexion->GetArrayInfo($resultDT)){
		            // Verificamos si hay alguna apuesta ganadora...
		    	    for ($i=0;$i<count($resultados);$i++){	
		    	    	$terminal_abajo=0;
		    	    	$terminal_arriba=0;
		    	    	if($rowDT['id_tipo_jugada']==2){
			    	    	switch ($aprox){
								case 0:
									$terminal_abajo=$rowDT['numero']-1;						
								break;
								case 1:
									$terminal_arriba=$rowDT['numero']+1;
									$terminal_abajo=$rowDT['numero']-1;
								break;
								case 2:
									$terminal_arriba=$rowDT['numero']+1;
								break;
			    	    	}
			    	    	if(($terminal_abajo==substr($resultados[$i], 1, 3) OR $terminal_arriba==substr($resultados[$i], 1, 3)) AND $rowDT['id_sorteo']==$id_sorteo[$i] ){
								$monto_pago=$relacion_pago[5][$roww['id_agencia']]*$rowDT['monto'];
								echo "<br>Monto Pago: ".$monto_pago;
			    	    		echo "Pasa";
			    	    		if($tipo_servidor==1 OR $tipo_servidor==2)
								$id_detalle_ticket=$rowDT['id_detalle_ticket'];
			    	    		else 
			    	    		if($fecha_hora<$fecha_actual)
								$id_detalle_ticket=$rowDT['id_detalle_ticket'];
								else
								$id_detalle_ticket=$rowDT['id_detalle_ticket_diario'];
			    	    		$monto_total+=$monto_pago;
			  					$obj_modelo->PremiarDetalleTicket2($id_detalle_ticket, $monto_pago);
			  					$sw=1;
			    	    	}
		    	    	}
		    	    	if( $rowDT['id_zodiacal']==$id_zodiacal[$i]  AND ((($rowDT['numero']==$resultados[$i] AND ($rowDT['id_tipo_jugada']==1 OR $rowDT['id_tipo_jugada']==3))OR ($rowDT['numero']== substr($resultados[$i], 1, 3) AND ($rowDT['id_tipo_jugada']==2 OR $rowDT['id_tipo_jugada']==4))    )      AND $rowDT['id_sorteo']==$id_sorteo[$i] )){
							$monto_pago=$relacion_pago[$rowDT['id_tipo_jugada']][$roww['id_agencia']]*$rowDT['monto'];
							$monto_total+=$monto_pago;
							echo "Pasa";
							if($tipo_servidor==1 OR $tipo_servidor==2)
							$id_detalle_ticket=$rowDT['id_detalle_ticket'];
							else
							if($fecha_hora<$fecha_actual)
							$id_detalle_ticket=$rowDT['id_detalle_ticket'];
							else
							$id_detalle_ticket=$rowDT['id_detalle_ticket_diario'];
							$obj_modelo->PremiarDetalleTicket2($id_detalle_ticket, $monto_pago);
							$sw=1;
						}
					}	
				}
		    	if($sw==1)
		    	$obj_modelo->PremiarTicket2($id_ticket,$monto_total);
		    }
	    }
	}
	$fecha_hora = strtotime ( '+1 day' , strtotime ( $fecha_hora));
	if( date ( 'l' , strtotime($fecha_hora))=='Sunday')
	$fecha_hora = strtotime ( '+1 day' , strtotime ( $fecha_hora));
	$fecha_hora=date('Y-m-d',$fecha_hora );
}
    	   
    	    	

?>