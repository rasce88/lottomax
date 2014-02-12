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
require('.'.$obj_config->GetVar('ruta_modelo').'Ventas.php');
$obj_modelo= new Ventas($obj_conexion);

session_start();

// Obtenemos los datos de la taquilla
$taquilla= $obj_modelo->GetIdTaquilla();

//Funciones
function CalculaIncompletoYnuevoMonto($monto_disponible, $monto_jugado){

	$matriz=array();

	// Matriz[0]= El monto  para cubrir el faltante
	// Matriz[1]= El switch de ser incompleto o no
	// Matriz[2]= El monto por el que realmente se esta jugando


	//calculando el faltante entre el numero ya jugado y el nuevo por jugar
	$montodiferencia= ($monto_disponible-$monto_jugado);

	//si no completa el monto solicitado
	if ($montodiferencia < 0){
		//Mensaje de ERROR -- NUMERO INCOMPLETO PARA ESTE SORTEO
		//el nuevo disponible es el faltante del incompleto
		$matriz[0] = $montodiferencia;
		$matriz[1] = 1;
		$matriz[2] = $montodiferencia+$monto_jugado;
		//echo "<br> nuevo ".$num_jug_nuevodisponible;
	}else
		if ($montodiferencia ==0)
		{
			//	echo "pasa";
			$matriz[0] = 0;
			$matriz[1] = 3;
			$matriz[2] = $monto_jugado;
		}
		else
		{
			//el nuevo disponible es el restante para numeros_jugados
			$matriz[0] = $montodiferencia;
			$matriz[1] = 0;
			$matriz[2] = $monto_jugado;
		}
		 
		// $matriz2=CalculaIncompletoYnuevoMonto();
		// echo $matriz2[0]; // nuevomontodisponible o faltante
		// echo $matriz2[1]; // incompleto
		// echo $matriz2[2]; // monto real disponible para el ticket o monto solicitado
		//  print_r($matriz);
		return $matriz;

}

function ProcesoCupos($txt_numero,$txt_monto, $sorteo, $zodiacal, $esZodiacal){

	global $taquilla;
	global $obj_modelo;
	global $obj_conexion;

	//determinando el tipo de jugada
	$id_tipo_jugada= $obj_modelo->GetTipoJugada($esZodiacal,$txt_numero);


	//revisar tabla de ticket_transaccional
	$numero_jugadoticket= $obj_modelo->GetTicketTransaccional($txt_numero,$sorteo,$zodiacal, $id_tipo_jugada);

	if ( $numero_jugadoticket['total_registros']>0 ){

		//adicionar y dar mensaje de confirm (adicionar al ticket u obviar)

		//significa que ya existe y debemos ver el monto que queda
		$num_ticket_faltante = $numero_jugadoticket['monto_faltante'];
		$num_ticket_monto = $numero_jugadoticket['monto'];
		$num_ticket_inc = $numero_jugadoticket['incompleto'];

		//Verificando que si esta incompleto
		if ($num_ticket_inc == '1'){
				
			// ya no se puede anadir, mas bien le falto por jugar
			//echo "El numero ya esta jugado y tiene su cupo completo";
				
			//$_SESSION['mensaje']= $mensajes['numero_repetido_eincompleto'];
			echo "<div id='mensaje' class='mensaje' >El numero ya esta jugado y tiene su cupo completo !!!</div>";
				
		}else{
			if ($num_ticket_inc == '0'){
				/************* CABLEADO **********************/
				//Proceso CONFIRM: Elimina la apuesta existente en ticket transaccional, y para que no este repetida, la registra
				// con el nuevo monto ingresado.

				$id_ticket_transaccional= $obj_modelo->GetIDTicketTransaccional($txt_numero,$sorteo,$zodiacal);


				//echo "<input id='txt_id_ticket_transaccional' name='txt_id_ticket_transaccional' type='text' value='".$id_ticket_transaccional."'/>";
				$obj_modelo->EliminarTicketTransaccionalByTicket($id_ticket_transaccional);
				$result = ProcesoCupos($txt_numero, $txt_monto, $sorteo, $zodiacal, $esZodiacal);

				echo "--";
			}
		}

	}else{

		//revisar tabla de numeros_jugados
		$numero_jugado= $obj_modelo->GetNumerosJugados($txt_numero,$sorteo,$zodiacal);

		//significa que ya existe y debemos ver el monto que queda
		$num_jug = $numero_jugado['monto_restante'];
		if( $numero_jugado['total_registros']>0 ){
			//echo $num_jug;
			//print_r($numero_jugado);

			//si queda por un monto mayor que 0
			if ($num_jug >0){
				$matriz2= CalculaIncompletoYnuevoMonto($txt_monto, $num_jug);
				// Guardar ticket a tabla transaccional
				if( $obj_modelo->GuardarTicketTransaccional($txt_numero,$sorteo,$zodiacal,$id_tipo_jugada,$matriz2[0],$matriz2[1],$matriz2[2],$taquilla) ){

				}else{

					$_SESSION['mensaje']= $mensajes['fallo_agregar_ticket'];
					echo "<div id='mensaje' class='mensaje' >".$_SESSION['mensaje']."</div>";
				}

			}else{
					
				//Mensaje de ERROR -- NUMERO AGOTADO PARA ESTE SORTEO
					
				//Se registra el numero como agotado
				$obj_modelo->GuardarTicketTransaccional($txt_numero,$sorteo,$zodiacal,$id_tipo_jugada,$txt_monto,2,0,$taquilla);
				$_SESSION['mensaje']= $txt_numero." AGOTADO para sorteo ".$obj_modelo->GetNombreSorteo($sorteo)."  ".$obj_modelo->GetPreZodiacal($zodiacal);
				echo "<div id='mensaje' class='mensaje' >".$_SESSION['mensaje']."</div>";

			}
				
		}else{

			//No existe aun
			//revisar tabla de cupo_especial
			$cupo_especial= $obj_modelo->GetCuposEspeciales($txt_numero,$sorteo,$zodiacal);

			if( $cupo_especial['total_registros']>0 ){
					
				while($row= $obj_conexion->GetArrayInfo($cupo_especial['result'])){

					//recortando el formato 2013-02-26 00:00:00 a 2013-02-26
					$fecha_desde = substr($row["fecha_desde"], 0, 10);
					$fecha_hasta = substr($row["fecha_hasta"], 0, 10);

					//funcion para saber si hoy esta entre dos fechas dadas
					// regresa 1 si esta entre las fechas; 0 de lo contrario
					$fecha_efectiva= $obj_modelo->entreFechasYhoy($fecha_desde,$fecha_hasta);

					//si esta entre las fechas del bloqueo
					if ($fecha_efectiva == 1){
							
						//significa que ya existe y debemos ver el monto que queda
						$monto_cupoespecial= $row["monto_cupo"];

						//si queda por un monto mayor que 0
						if ($monto_cupoespecial >0){

							$num_jug= $txt_monto;

							 

							//registrar $num_jug_nuevodisponible, $incompleto
							$matriz2= CalculaIncompletoYnuevoMonto($monto_cupoespecial, $num_jug);
								
							if( $obj_modelo->GuardarTicketTransaccional($txt_numero,$sorteo,$zodiacal,$id_tipo_jugada,$matriz2[0],$matriz2[1],$matriz2[2],$taquilla) ){

							}
							else{
								$_SESSION['mensaje']= $mensajes['fallo_agregar_ticket'];
								echo "<div id='mensaje' class='mensaje' >".$_SESSION['mensaje']."</div>";
							}

						}else{
							//Mensaje de ERROR -- NUMERO BLOQUEADO PARA ESTE SORTEO

							//Se registra el numero como agotado
							$obj_modelo->GuardarTicketTransaccional($txt_numero,$sorteo,$zodiacal,$id_tipo_jugada,$txt_monto,2,0,$taquilla);

							$_SESSION['mensaje']= $txt_numero." AGOTADO para sorteo ".$obj_modelo->GetNombreSorteo($sorteo)."  ".$obj_modelo->GetPreZodiacal($zodiacal);
							echo "<div id='mensaje' class='mensaje' >".$_SESSION['mensaje']."</div>";
						}

					}else{
						//No posee cupo especial, ni esta en tabla numeros_jugados
						//revisar tabla de cupo_general
						//procesa A

						//determinando monto_cupo segun id tipo de jugada
						$cupo_general= $obj_modelo->GetCuposGenerales($id_tipo_jugada);

						// Asignamos al monto restante el monto de apuesta para efectos de la funcion CalculaIncompletoYnuevoMonto
						$num_jug= $txt_monto;

						// Calculamos el valor de incompleto


						// Calculando $num_jug_nuevodisponible,$incompleto
						$matriz2= CalculaIncompletoYnuevoMonto($cupo_general, $num_jug);
							
						if( $obj_modelo->GuardarTicketTransaccional($txt_numero,$sorteo,$zodiacal,$id_tipo_jugada,$matriz2[0],$matriz2[1],$matriz2[2],$taquilla) ){

						}
						else{
							$_SESSION['mensaje']= $mensajes['fallo_agregar_ticket'];
							echo "<div id='mensaje' class='mensaje' >".$_SESSION['mensaje']."</div>";
						}
							
							
					}


				}
					
			}else{
				//No posee cupo especial, ni esta en tabla numeros_jugados
				//revisar tabla de cupo_general
				//procesa A

				//echo "PASA";
				//determinando monto_cupo segun id tipo de jugada
				$cupo_general= $obj_modelo->GetCuposGenerales($id_tipo_jugada);

				// Asignamos al monto restante el monto de apuesta para efectos de la funcion CalculaIncompletoYnuevoMonto
				$num_jug= $txt_monto;

				// Calculamos el valor de incompleto
				 

				// Calculando $num_jug_nuevodisponible,$incompleto
				$matriz2= CalculaIncompletoYnuevoMonto($cupo_general, $num_jug);

				 

				// Guardar ticket a tabla transaccional
				if( $obj_modelo->GuardarTicketTransaccional($txt_numero,$sorteo,$zodiacal,$id_tipo_jugada,$matriz2[0],$matriz2[1],$matriz2[2],$taquilla) ){

				}
				else{
					//$_SESSION['mensaje']= $mensajes['fallo_agregar_ticket'];
					$_SESSION['mensaje']= "Error No se logro ingresar la jugada al ticket!!!";
					echo "<div id='mensaje' class='mensaje' >".$_SESSION['mensaje']."</div>";
				}
					
			}



		}
			


		return 1;

	}
}


// Inicio del proceso
$resultTT= $obj_modelo->GetDatosTicketTransaccional();
If ($obj_conexion->GetNumberRows($resultTT)>0){
    $catidad_apuestas = $obj_conexion->GetNumberRows($resultTT);
    $txt_monto = $_GET['monto'];

    $monto_prorrateado= round($txt_monto/$catidad_apuestas, 2,PHP_ROUND_HALF_EVEN);
    
   // echo $monto_prorrateado;

    $obj_modelo->EliminarTicketTransaccionalByTaquilla($taquilla);
    while($row= $obj_conexion->GetArrayInfo($resultTT)){
       $txt_numero = $row['numero'];
       $sorteo = $row['id_sorteo'];
       $id_zodiacal=$row['id_zodiacal'];
       $id_ticket_transaccional = $row['id_ticket_transaccional'];  
       $result= ProcesoCupos($txt_numero, $monto_prorrateado, $sorteo, $id_zodiacal, 0);
    }
    

}



// Listado de Jugadas Agregadas
if( $result= $obj_modelo->GetDatosTicketTransaccional() ){
    echo "<br><table class='table_ticket' align='center' border='1' width='90%'>";
    while($row= $obj_conexion->GetArrayInfo($result)){
            $inc = "";
            $span1="";
            $span2="";

            //determinando si es un numero inc..
            if($row['incompleto'] == 1){
                    $inc = "Inc ...";
                    $span1="<span class='requerido'>";
                    $span2="</span>";
            }

            $zodiacal="";
            // determinando el signo zodiacal... si lo hay...
            if($row['id_zodiacal'] <> 0){
                    $zodiacal = $obj_modelo->GetPreZodiacal($row['id_zodiacal']);
            }

            //print_r($row);
            echo "<tr class='eveno'><td align='center'>".$span1."SORTEO: ".$obj_modelo->GetNombreSorteo($row['id_sorteo']).$span2."</td></tr>";
            echo "<tr class='eveni'><td align='left'>".$span1.$row['numero']." x ".$row['monto']." ".$zodiacal." ".$inc.$span2."</td></tr>";
    }
    echo "</table>";
}

?>