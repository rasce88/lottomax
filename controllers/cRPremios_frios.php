<?php
/**
 * Archivo del controlador para modulo de Reporte de Premios Frios
 * @package cRPremios_frios.php
 * @author Brenda Batista B. - <Brebatista@gmail.com>
 * @copyright Grupo de empresas Voila
 * @license BSD License
 * @version v 1.0 Mayo - 2013
 */

// Vista asignada
$obj_xtpl->assign_file('contenido', $obj_config->GetVar('ruta_vista').'Rpremios_frios'.$obj_config->GetVar('ext_vista'));

// Modelo asignado
require($obj_config->GetVar('ruta_modelo').'RPremios_frios.php');

$obj_modelo= new RPremios_frios($obj_conexion);

require('/fpdf/fpdf.php');

switch (ACCION){

    case 'listar_resultados':
        $fecha = $_GET['txt_fecha'];
        $obj_xtpl->assign('fecha', $fecha);
        
        // Ruta actual
        $_SESSION['Ruta_Lista']= $obj_generico->RutaRegreso();

        // Ruta regreso
        $obj_xtpl->assign('ruta_regreso', $_SESSION['Ruta_Form']);
        
        $i=0;
        if( $result= $obj_modelo->GetTicketsGanadores($fecha) ){
            if ($obj_conexion->GetNumberRows($result)>0 ){
                
                while($row= $obj_conexion->GetArrayInfo($result)){
                    $tiempo_vigencia = $obj_modelo->TiempoVigencia();
                    $fecha_ticket =strtotime($row['fecha_hora']);
                    $fecha_actual =strtotime(date('Y-m-d'));
                    $fecha_vencido_ticket = strtotime("+$tiempo_vigencia days", strtotime($fecha_ticket));
                     if ($fecha_vencido_ticket < $fecha_actual) {
                          if( ($i % 2) >0){
                                $obj_xtpl->assign('estilo_fila', 'even');
                            }
                            else{
                                    $obj_xtpl->assign('estilo_fila', 'odd');
                            }

                            $obj_xtpl->assign('fecha_hora', $row['fecha_hora']);
                            $obj_xtpl->assign('id_ticket', $row['id_ticket']);
                            $obj_xtpl->assign('taquilla', $row['taquilla']);
                            $obj_xtpl->assign('total', $row['total_ticket']);
                            $obj_xtpl->assign('total_premiado', $row['total_premiado']);

                            $link_detalle= $_SESSION['Ruta_Form']."&accion=ver_detalle&id_ticket=".$row['id_ticket'];
                            $obj_xtpl->assign('link_detalle', $link_detalle);

                            // Parseo del bloque de la fila
                            $obj_xtpl->parse('main.contenido.lista_resultados.lista');
                            $i++;
                     }
                }
                 // Parseo del bloque de la fila
                $obj_xtpl->parse('main.contenido.lista_resultados');
            }else{
                // Mensaje
                $obj_xtpl->assign('no_info',$mensajes['sin_lista']);
//                        // Mensaje
//                        $obj_xtpl->assign('sin_listado',$mensajes['sin_lista']);
//
//                        // Parseo del bloque de la fila
//                        $obj_xtpl->parse('main.contenido.lista_resultados.no_lista');
                }

           
        }
        
        break;

    case 'ver_resultados':

        // Creamos el PDF

   

        //Creación del objeto de la clase heredada
        $pdf=new FPDF();
        
        $pdf->AliasNbPages();
        
        //Primera página
        $pdf->AddPage();

        $fecha = $_GET['fecha'];


        // Imagen  de encabezado
        $pdf->Image("./images/banner4.jpg" , 0 ,0, 200 ,40  , "JPG" ,"");
        
        // Titulo del Reporte
            $pdf->SetFont('Arial','B',20);
            $pdf->SetY(45);
            $pdf->Cell(50,10,'Tickets Ganadores a la fecha '.$fecha);


            
        // Configuracion de colores
            $pdf->SetY(60);
            $pdf->SetFillColor(224,235,255);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(128,0,0);
            $pdf->SetLineWidth(.3);
            $pdf->SetFont('','B');

        
         if( $result= $obj_modelo->GetTicketsGanadores($fecha) ){
            if ($obj_conexion->GetNumberRows($result)>0 ){
                // Establecemos la cabecera de la tabla
                $pdf->SetFont('Arial','B',10);
                $pdf->SetTextColor(128,0,0);
                $pdf->Cell(40,7,'Fecha Hora',1,0,'C',true);
                $pdf->Cell(30,7,'Numero Ticket',1,0,'C',true);
                $pdf->Cell(30,7,'Taquilla',1,0,'C',true);
                $pdf->Cell(30,7,'Total',1,0,'C',true);
                $pdf->Cell(30,7,'Total Premio',1,0,'C',true);                

                $pdf->SetFont('Arial','',8);
                while($row= $obj_conexion->GetArrayInfo($result)){

                    $tiempo_vigencia = $obj_modelo->TiempoVigencia();
                    $fecha_ticket =strtotime($row['fecha_hora']);
                    $fecha_actual =strtotime(date('Y-m-d'));
                    $fecha_vencido_ticket = strtotime("+$tiempo_vigencia days", strtotime($fecha_ticket));
                     if ($fecha_vencido_ticket < $fecha_actual) {
                            $pdf->Ln();
                            $pdf->SetTextColor(0);
                            $pdf->Cell(40,7,$row['fecha_hora'],1);
                            $pdf->Cell(30,7,$row['id_ticket'],1,0,'C');
                            $pdf->Cell(30,7,$row['taquilla'],1,0,'C');
                            $pdf->Cell(30,7,$row['total_ticket'],1,0,'C');
                            $pdf->Cell(30,7,$row['total_premiado'],1,0,'C');

                     }
                    
                }
            }else{  
                $pdf->SetFont('Arial','B',14);
                $pdf->SetTextColor(0);
                $pdf->SetY(80);
                $pdf->Cell(10,10,'No se han ingresado resultados a la fecha...');
            }

         }
  
        $pdf->Output();

        break;
        case 'ver_detalle':

        // Creamos el PDF



        //Creación del objeto de la clase heredada
        $pdf=new FPDF();

        $pdf->AliasNbPages();

        //Primera página
        $pdf->AddPage();

        $id_ticket = $_GET['id_ticket'];


        // Imagen  de encabezado
        $pdf->Image("./images/banner4.jpg" , 0 ,0, 200 ,40  , "JPG" ,"");

        // Titulo del Reporte
            $pdf->SetFont('Arial','B',20);
            $pdf->SetY(45);
            $pdf->Cell(50,10,'Detalle del Ticket No. '.$id_ticket);



        // Configuracion de colores
            $pdf->SetY(60);
            $pdf->SetFillColor(224,235,255);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(128,0,0);
            $pdf->SetLineWidth(.3);
            $pdf->SetFont('','B');


         if( $result= $obj_modelo->GetDetalleTicket($id_ticket) ){
            if ($obj_conexion->GetNumberRows($result)>0 ){
                // Establecemos la cabecera de la tabla
                $pdf->SetFont('Arial','B',10);
                $pdf->SetTextColor(128,0,0);
                $pdf->Cell(20,7,'Numero',1,0,'C',true);
                $pdf->Cell(40,7,'Sorteo',1,0,'C',true);
                $pdf->Cell(30,7,'Hora Sorteo',1,0,'C',true);
                $pdf->Cell(30,7,'Signo',1,0,'C',true);
                $pdf->Cell(30,7,'Monto',1,0,'C',true);
                $pdf->Cell(40,7,'Apuesta Ganadora',1,0,'C',true);

                $pdf->SetFont('Arial','',8);
                while($row= $obj_conexion->GetArrayInfo($result)){
                    $pdf->Ln();
                    $pdf->SetTextColor(0);
                    $pdf->Cell(20,7,$row['numero'],1,0,'C');
                    $pdf->Cell(40,7,$row['nombre_sorteo'],1,0,'C');
                    $pdf->Cell(30,7,$row['hora_sorteo'],1,0,'C');
                    $pdf->Cell(30,7,$row['nombre_zodiacal'],1,0,'C');
                    $pdf->Cell(30,7,$row['monto'],1,0,'C');
                    if ($row['premiado'] == '1'){
                        $premiado='Si';
                    }else{
                        $premiado='No';
                    }
                    $pdf->Cell(40,7,$premiado,1,0,'C');
                }
            }else{
                $pdf->SetFont('Arial','B',14);
                $pdf->SetTextColor(0);
                $pdf->SetY(80);
                $pdf->Cell(10,10,'No existe informacion...');
            }

         }

        $pdf->Output();

        break;
    default:

            // Ruta actual
            $_SESSION['Ruta_Form']= $obj_generico->RutaRegreso();

            $obj_xtpl->assign('fecha', date('Y-m-d'));
            // Parseo del bloque
            $obj_xtpl->parse('main.contenido.buscar_tickets');

            break;

}
$obj_xtpl->parse('main.contenido');


?>