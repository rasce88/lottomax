<?php
/**
 * Archivo del controlador para modulo de Reporte de Ventas por periodo
 * @package cRVentas_periodo.php
 * @author Brenda Batista B. - <Brebatista@gmail.com>
 * @copyright Grupo de empresas Voila
 * @license BSD License
 * @version v 1.0 Junio - 2013
 */

// Vista asignada
$obj_xtpl->assign_file('contenido', $obj_config->GetVar('ruta_vista').'RVentas_periodo'.$obj_config->GetVar('ext_vista'));

// Modelo asignado
require($obj_config->GetVar('ruta_modelo').'RVentas_periodo.php');

$obj_modelo= new RVentas_periodo($obj_conexion);

require('/fpdf/fpdf.php');

switch (ACCION){

    case 'listar_resultados':

        
        
        // Ruta actual
        $_SESSION['Ruta_Lista']= $obj_generico->RutaRegreso();

        // Ruta regreso
        $obj_xtpl->assign('ruta_regreso', $_SESSION['Ruta_Form']);
        
        $fecha_desde= $obj_generico->CleanText($_GET['fechadesde']);
        $fecha_hasta= $obj_generico->CleanText($_GET['fechahasta']);
        $taquilla= $obj_generico->CleanText($_GET['op_taquilla']);
        $sorteo= $obj_generico->CleanText($_GET['sorteo']);

        $obj_xtpl->assign('fechadesde', $fecha_desde);
        $obj_xtpl->assign('fechahasta', $fecha_hasta);
        $obj_xtpl->assign('taquilla', $taquilla);
        $obj_xtpl->assign('sorteo', $sorteo);

        $i=0; $total_ventas=0; $cantidad_anulados=0;

        if( $result= $obj_modelo->GetTickets($fecha_desde, $fecha_hasta, $taquilla, $sorteo) ){
            if ($obj_conexion->GetNumberRows($result)>0 ){
                
                while($row= $obj_conexion->GetArrayInfo($result)){
                    

                    if ($row['status']=='0'){
                        $cantidad_anulados++;
                    }else{
                        if( ($i % 2) >0){
                            $obj_xtpl->assign('estilo_fila', 'even');
                        }
                        else{
                                $obj_xtpl->assign('estilo_fila', 'odd');
                        }

                        $obj_xtpl->assign('fecha_hora', $row['fecha_hora']);
                        $obj_xtpl->assign('id_ticket', $row['id_ticket']);
                        $obj_xtpl->assign('total', $row['total_ticket']);

                        $link_detalle= $_SESSION['Ruta_Form']."&accion=ver_detalle&id_ticket=".$row['id_ticket'];
                        $obj_xtpl->assign('link_detalle', $link_detalle);

                        // Parseo del bloque de la fila
                        $obj_xtpl->parse('main.contenido.lista_resultados.lista');
                        $total_ventas= $total_ventas + $row['total_ticket'];
                        $i++;
                    }

                    
                }

                $obj_xtpl->assign('total_ventas', ' El total de ventas fue: Bs. F. '.$total_ventas);
                $obj_xtpl->assign('ticket_anulados', ' Cantidad de tickets anulados: '.$cantidad_anulados);
                
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

        $fecha_desde= $obj_generico->CleanText($_GET['fechadesde']);
        $fecha_hasta= $obj_generico->CleanText($_GET['fechahasta']);
        $taquilla= $obj_generico->CleanText($_GET['taquilla']);
        $sorteo= $obj_generico->CleanText($_GET['sorteo']);


        // Imagen  de encabezado
        $pdf->Image("./images/banner4.jpg" , 0 ,0, 200 ,40  , "JPG" ,"");
        
        // Titulo del Reporte
            $pdf->SetFont('Arial','B',20);
            $pdf->SetY(45);
            $pdf->Cell(50,10,'Tickets desde la fecha '.$fecha_desde.' hasta '.$fecha_hasta);


            
        // Configuracion de colores
            $pdf->SetY(80);
            $pdf->SetFillColor(224,235,255);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(128,0,0);
            $pdf->SetLineWidth(.3);
            $pdf->SetFont('','B');

        
         if( $result= $obj_modelo->GetTickets($fecha_desde, $fecha_hasta, $taquilla, $sorteo) ){
            if ($obj_conexion->GetNumberRows($result)>0 ){
                // Establecemos la cabecera de la tabla
                $pdf->SetFont('Arial','B',10);
                $pdf->SetTextColor(128,0,0);
                $pdf->Cell(40,7,'Fecha Hora',1,0,'C',true);
                $pdf->Cell(30,7,'Numero Ticket',1,0,'C',true);
                $pdf->Cell(30,7,'Total',1,0,'C',true);

                $pdf->SetFont('Arial','',8);
               
                $total_ventas=0; $cantidad_anulados=0;
                while($row= $obj_conexion->GetArrayInfo($result)){
                     if ($row['status']=='0'){
                        $cantidad_anulados++;
                    }else{
                        $pdf->Ln();
                        $pdf->SetTextColor(0);
                        $pdf->Cell(40,7,$row['fecha_hora'],1);
                        $pdf->Cell(30,7,$row['id_ticket'],1,0,'C');
                        $pdf->Cell(30,7,$row['total_ticket'],1,0,'C');

                        $total_ventas= $total_ventas + $row['total_ticket'];
                    }
                }

                $pdf->SetFont('Arial','B',12);
                $pdf->SetY(60);
                $pdf->Cell(50,10,' El total de ventas fue: Bs. F. '.$total_ventas);
                $pdf->SetY(65);
                $pdf->Cell(50,10,' Cantidad de tickets anulados: '.$cantidad_anulados);
                
            }else{  
                $pdf->SetFont('Arial','B',14);
                $pdf->SetTextColor(0);
                $pdf->SetY(80);
                $pdf->Cell(10,10,'No hay informacion');
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

            //Cargar taquilla
            if( $result = $obj_modelo->GetDatosTaquillas() ){
                while($row= $obj_conexion->GetArrayInfo($result)){
                    $obj_xtpl->assign('id_taquilla',$row['id_taquilla']);
                    $obj_xtpl->assign('numero_taquilla',$row['numero_taquilla']);

                    // Parseo del bloque de lista_tipo_jugadas
                    $obj_xtpl->parse('main.contenido.buscar_tickets.lista_taquillas');
                }
            }

             if( $result = $obj_modelo->GetSorteos() ){
                    while($row= $obj_conexion->GetArrayInfo($result)){
                        $obj_xtpl->assign('id_sorteo',$row['id_sorteo']);
                        $obj_xtpl->assign('nombre_sorteo',$row['nombre_sorteo']);


                        $obj_xtpl->parse('main.contenido.buscar_tickets.lista_sorteo');
                    }
                }

            // Parseo del bloque
            $obj_xtpl->parse('main.contenido.buscar_tickets');

            break;

}
$obj_xtpl->parse('main.contenido');


?>