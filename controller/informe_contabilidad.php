<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('ejercicio.php');
require_once 'plugins/facturacion_base/extras/inventarios_balances.php';
require_model('subcuenta.php');
require_model('recibo_proveedor.php');

class informe_contabilidad extends fs_controller
{
   public $ejercicio;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Contabilidad', 'informes', FALSE, TRUE);
   }
   
   protected function private_core()
   {
   		$this->subcuenta = new subcuenta();
      $this->ejercicio = new ejercicio();
	  $this->ppage = $this->page->get('libro_mayor_generar');
	if( isset($_REQUEST['buscar_subcuenta'])) 
      {

  	  $this->buscar_subcuenta();
      }
	  if( isset($_POST['codejer']) AND isset($_POST['query']) )
      {
         $this->new_search();
      }
      
      if( isset($_GET['libro_diario']) )
	  {
  //       $this->libro_diario_csv($_GET['diario']);
 //			$this->libro_diario_pdf($_GET['diario']);
 
 
      }
      else if( isset($_GET['balance']) AND isset($_GET['eje']) )
      {
         $this->template = FALSE;
         $iba = new inventarios_balances();
         
         if($_GET['balance'] == 'pyg')
         {
            $iba->generar_pyg($_GET['eje']);
         }
         else
            $iba->generar_sit($_GET['eje']);
      }
      else if( isset($_POST['balance_ss']) )
      {
         if(isset($_POST['codejercicio'])) $this->balance_sumas_y_saldos();
      }
	  else if( isset($_POST['balance_cvs']) )
      {
         if(isset($_POST['codejercicio'])) $this->balance_sumas_y_saldos_cvs();
      }
	  else if( isset($_GET['plan']) )
      {
	  		if( isset($_GET['eje']) )
				{
	  			$this->plan_de_cuentas();
				}
	  }
	  else if( isset($_POST['libro_diario']) )
      {
         if(isset($_POST['codejercicio'])) 
		 {
		 if($_POST['libro_diario'] == 1)
		 $this->libro_diario_pdf($_POST['codejercicio'],$_POST['desde'],$_POST['hasta']);
		 if($_POST['libro_diario'] == 2)
		 $this->libro_diario_csv($_POST['codejercicio'],$_POST['desde'],$_POST['hasta']);
		 		 
		 }
      }
   }
   
   public function existe_libro_diario($codeje)
   {
      return file_exists('tmp/'.FS_TMP_NAME.'libro_diario/'.$codeje.'.pdf');
   }
   
   public function existe_libro_inventarios($codeje)
   {
      return file_exists('tmp/'.FS_TMP_NAME.'inventarios_balances/'.$codeje.'.pdf');
   }
   
   private function libro_diario_csv($codeje,$desde,$hasta)
   {
   
      $this->template = FALSE;
      header("content-type:application/csv;charset=UTF-8");
      header("Content-Disposition: attachment; filename=\"diario.csv\"");
      echo "fecha;asiento;subcuenta;descripcion;;;concepto;;;debe;haber\n";
      
      $partida = new partida();
      $offset = 0;
	  $debD = 0;
	  $habD = 0;
	  $debT = 0;
	  $habT = 0;
      $partidas = $partida->subcuentas_por_fecha($codeje,$desde,$hasta, $offset);
	if(  $partidas )
	{
	  $asien_fecha = $partidas[0]['fecha'];
      
	  
         foreach($partidas as $par)
         {
		 if ( $asien_fecha == $par['fecha'])
		 {
            echo $par['fecha'].';'.$par['numero'].';'.$par['codsubcuenta'].';'.$par['descripcion'].';;;'.$par['concepto'].';;;'.round($par['debe'],2).';'.round($par['haber'],2)."\n";
            $offset++;
			$debD = $debD + round($par['debe'],2);
			$habD = $habD + round($par['haber'],2);
			$debT = $debT + round($par['debe'],2);
			$habT = $habT + round($par['haber'],2);
		 }	
		else
		{
			echo ' ; ; ;Total diario  ;'. $debD.';'.$habD."\n\n";
			
			$debD = 0;
	  		$habD = 0;
			echo $par['fecha'].';'.$par['numero'].';'.$par['codsubcuenta'].';'.$par['descripcion'].';;;'.$par['concepto'].';;;'.round($par['debe'],2).';'.round($par['haber'],2)."\n";
            $offset++;
			$debD = $debD + round($par['debe'],2);
			$habD = $habD + round($par['haber'],2);
			$debT = $debT + round($par['debe'],2);
			$habT = $habT + round($par['haber'],2);
			$asien_fecha = $par['fecha'];
		}	
			
         }
    }     
 //        $partidas = $partida->subcuentas_por_fecha($codeje,$desde,$hasta, $offset);
		 
     
	  echo ' ; ; ;Total diario  ;'. $debD.';'.$habD."\n\n";
	  
//	  echo ' ;  ; ;Totales ;'. $debT.';'.$habT;

}

private function libro_diario_pdf($codeje,$desde,$hasta)
   {
 	 $eje = $this->ejercicio->get($codeje);
      if($eje)
      {
         if( strtotime($desde) < strtotime($eje->fechainicio) OR strtotime($hasta) > strtotime($eje->fechafin) )
         {
            $this->new_error_msg('La fecha está fuera del rango del ejercicio.');
         }
         else
         {
            $this->template = FALSE;
            $pdf_doc = new fs_pdf('A4','portrait', 'Courier');
            $pdf_doc->pdf->addInfo('Title', 'Libro Diario de  ' . $this->empresa->nombre);
            $pdf_doc->pdf->addInfo('Subject', 'Libro Diario de ' . $this->empresa->nombre);
            $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
 //           $pdf_doc->pdf->ezStartPageNumbers(570, 800, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}');
			
			$iba = new inventarios_balances();
          
               $iba->libro_diario($pdf_doc, $eje, 'de '.$desde.' a '.$hasta, $desde, $hasta);
          
            
            $pdf_doc->show();
         }
      }
 }



  
   
      private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/subcuentas_listado';
      
      $this->cod_ejer = $_POST['codejer'];
         $this->resultados = $this->subcuenta->search_by_ejercicio($this->cod_ejer, $this->query);
		 
   
   }
   
   
    public function url()
   {
      
         return 'index.php?page=informe_contabilidad&hab_subc=1';
   }
   
   
   private function balance_sumas_y_saldos()
   {
      $eje = $this->ejercicio->get($_POST['codejercicio']);
      if($eje)
      {
         if( strtotime($_POST['desde']) < strtotime($eje->fechainicio) OR strtotime($_POST['hasta']) > strtotime($eje->fechafin) )
         {
            $this->new_error_msg('La fecha está fuera del rango del ejercicio.');
         }
         else
         {
            $this->template = FALSE;
            $pdf_doc = new fs_pdf();
            $pdf_doc->pdf->addInfo('Title', 'Balance de situación de ' . $this->empresa->nombre);
            $pdf_doc->pdf->addInfo('Subject', 'Balance de situación de ' . $this->empresa->nombre);
            $pdf_doc->pdf->addInfo('Author', $this->empresa->nombre);
            $pdf_doc->pdf->ezStartPageNumbers(570, 800, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}');
            
            $excluir = FALSE;
            if( isset($eje->idasientocierre) AND isset($eje->idasientopyg) )
            {
               $excluir = array($eje->idasientocierre, $eje->idasientopyg);
            }
            
            $iba = new inventarios_balances();
            
            if($_POST['tipo'] == '3')
            {
               $iba->sumas_y_saldos3($this->db, $pdf_doc, $eje, 'de '.$_POST['desde'].' a '.$_POST['hasta'], $_POST['desde'], $_POST['hasta'], $excluir, FALSE);
            }
            else if($_POST['tipo'] == '4')
            {
               $iba->sumas_y_saldos_all($pdf_doc, $eje, 'de '.$_POST['desde'].' a '.$_POST['hasta'], $_POST['desde'], $_POST['hasta'], $excluir, FALSE);
            }
			else
			{
			$iba->sumas_y_saldos($pdf_doc, $eje, 'de '.$_POST['desde'].' a '.$_POST['hasta'], $_POST['desde'], $_POST['hasta'], $excluir, FALSE);
			}
            
            $pdf_doc->show();
         }
      }
   }
   
   
   
   
   private function plan_de_cuentas()
      {
      $eje = $this->ejercicio->get($_GET['eje']);
      if($eje)
      {
         if( !$eje->fechainicio || !$eje->fechafin )
         {
            $this->new_error_msg('La fecha está fuera del rango del ejercicio.');
         }
         else
         {
		      $this->template = FALSE;
			  header("content-type:application/csv;charset=UTF-8");
			  header("Content-Disposition: attachment; filename=\"Plan_de_Cuentas_".$eje->codejercicio.".csv\"");
	//		  echo utf8_decode("Cuenta;Descripción;;;;Acumulado Debe;;Acumulado Haber;;Saldo Deudo;;Saldo Acreedor\n");
				echo utf8_decode("Cuenta;Descripción;;;;\n");
			$fechaini = $eje->fechainicio;
			$fechafin =	$eje->fechafin;		
						  $tdebe = 0;
						  $thaber = 0;
						  $tsaldod = 0;
						  $tsaldoa = 0;
						  $pgrupo = new pgrupo_epigrafes();
						  $partida = new partida();
						  
						  /// metemos todo en una lista
						  $auxlist = array();
						  $offset = 0;
						  $pgrupo = $pgrupo->all_from_ejercicio($eje->codejercicio, $offset);
						  
					  
					
								
							 foreach($pgrupo as $pg)
							 {
									$grupo = $pg->get_grupo();
									$debe = 0;
									$haber = 0;
									$auxt = $partida->totales_pgrupo_all($pg->codpgrupo, $fechaini, $fechafin);
											   $debe = $auxt['debe'];
											   $haber = $auxt['haber'];
	//								if( $debe!=0 OR $haber!=0)
									{
											if( $debe-$haber > 0)
											   {
											   $saldo_d = $debe-$haber;
											   $saldo_a = 0;
											   }
											   else
											   {
											   $saldo_d = 0;
											   $saldo_a = $haber-$debe;
											   }
	//							echo html_entity_decode($pg->codpgrupo.';'.$pg-descripcion.';;;;'.$debe.';;'.$haber.';;'.$saldo_d.';;'.$saldo_a."\n");
								echo html_entity_decode($pg->codpgrupo.';'.$pg->descripcion."\n");
											$tdebe += $debe;
											$thaber += $haber;
											$tsaldod += $saldo_d;
											$tsaldoa += $saldo_a;
									}				
												
								foreach($grupo as $g)
								{
									$epigrafe = $g->get_epigrafes();
									$debe = 0;
									$haber = 0;
					
									$auxt = $partida->totales_grupo_all($g->codgrupo, $fechaini, $fechafin);
											   $debe = $auxt['debe'];
											   $haber = $auxt['haber'];
	//								if( $debe!=0 OR $haber!=0)
									{
												if( $debe-$haber > 0)
											   {
											   $saldo_d = $debe-$haber;
											   $saldo_a = 0;
											   }
											   else
											   {
											   $saldo_d = 0;
											   $saldo_a = $haber-$debe;
											   }
				//			echo html_entity_decode($g->codgrupo.';  '.$g->descripcion.';;;;'.$debe.';;'.$haber.';;'.$saldo_d.';;'.$saldo_a."\n");	
							echo html_entity_decode($g->codgrupo.';  '.$g->descripcion."\n");	
						   
									}				
									
									foreach($epigrafe as $e)
									{
										$cuentas = $e->get_cuentas();
										$debe = 0;
										$haber = 0;
										$auxt = $partida->totales_epigrafe_all($e->codepigrafe, $fechaini, $fechafin);
											   $debe = $auxt['debe'];
											   $haber = $auxt['haber'];
	//									if( $debe!=0 OR $haber!=0)
										{
												if( $debe-$haber > 0)
											   {
											   $saldo_d = $debe-$haber;
											   $saldo_a = 0;
											   }
											   else
											   {
											   $saldo_d = 0;
											   $saldo_a = $haber-$debe;
											   }
	//						echo html_entity_decode($e->codepigrafe.';    '.$e->descripcion.';;;;'.$debe.';;'.$haber.';;'.$saldo_d.';;'.$saldo_a."\n");
							echo html_entity_decode($e->codepigrafe.';    '.$e->descripcion."\n");										
										}	
										
										foreach($cuentas as $c)
										{
										$subcuentas = $c->get_subcuentas();
										$debe = 0;
										$haber = 0;
										$auxt = $partida->totales_cuenta_all($c->codcuenta, $fechaini, $fechafin);
											   $debe = $auxt['debe'];
											   $haber = $auxt['haber'];
//										if( $debe!=0 OR $haber!=0)
										{
												if( $debe-$haber > 0)
											   {
											   $saldo_d = $debe-$haber;
											   $saldo_a = 0;
											   }
											   else
											   {
											   $saldo_d = 0;
											   $saldo_a = $haber-$debe;
											   }
//						echo html_entity_decode($c->codcuenta.';      '.$c->descripcion.';;;;'.$debe.';;'.$haber.';;'.$saldo_d.';;'.$saldo_a."\n");  
						echo html_entity_decode($c->codcuenta.';      '.$c->descripcion."\n");						
										}
											foreach($subcuentas as $sc)
											{
										$debe = 0;
										$haber = 0;
							
										$auxt = $partida->totales_subcuenta_all($sc->codsubcuenta, $fechaini, $fechafin);
											   $debe = $auxt['debe'];
											   $haber = $auxt['haber'];
	//									if( $debe!=0 OR $haber!=0)
													{
															if( $debe-$haber > 0)
														   {
														   $saldo_d = $debe-$haber;
														   $saldo_a = 0;
														   }
														   else
														   {
														   $saldo_d = 0;
														   $saldo_a = $haber-$debe;
														   }
						
							
//							echo utf8_decode($sc->codsubcuenta.';        '.html_entity_decode($sc->descripcion).';;;;'.$debe.';;'.$haber.';;'.$saldo_d.';;'.$saldo_a."\n");							   
							echo utf8_decode($sc->codsubcuenta.';        '.html_entity_decode($sc->descripcion)."\n");							   
													}							   
											}	
										}					
									}				
								}
								
					
								$offset++;
							 }
					 ////////////////////////////////////////////
						echo "\n\n\n";	
//						echo ';;Totales : ;;;'.$tdebe.';;'.$thaber.';;'.$tsaldod.';;'.$tsaldoa."\n";
					   
					


         }
      }
   }


   
   
   
      private function balance_sumas_y_saldos_cvs()
   {
      $eje = $this->ejercicio->get($_POST['codejercicio']);
      if($eje)
      {
         if( strtotime($_POST['desde']) < strtotime($eje->fechainicio) OR strtotime($_POST['hasta']) > strtotime($eje->fechafin) )
         {
            $this->new_error_msg('La fecha está fuera del rango del ejercicio.');
         }
         else
         {
		      $this->template = FALSE;
			  header("content-type:application/csv;charset=UTF-8");
			  header("Content-Disposition: attachment; filename=\"Balance_Sumas_".$eje->codejercicio.".csv\"");
			  echo utf8_decode("Cuenta;Descripción;;;;Acumulado Debe;;Acumulado Haber;;Saldo Deudo;;Saldo Acreedor\n");
		
			$fechaini = $_POST['desde'];
			$fechafin =	$_POST['hasta'];		
						  $tdebe = 0;
						  $thaber = 0;
						  $tsaldod = 0;
						  $tsaldoa = 0;
						  $pgrupo = new pgrupo_epigrafes();
						  $partida = new partida();
						  
						  /// metemos todo en una lista
						  $auxlist = array();
						  $offset = 0;
						  $pgrupo = $pgrupo->all_from_ejercicio($eje->codejercicio, $offset);
						  
					  
					
								
							 foreach($pgrupo as $pg)
							 {
									$grupo = $pg->get_grupo();
									$debe = 0;
									$haber = 0;
									$auxt = $partida->totales_pgrupo($pg->codpgrupo, $fechaini, $fechafin);
											   $debe = round($auxt['debe'],2);
											   $haber = round($auxt['haber'],2);
									if( $debe!=0 OR $haber!=0)
									{
											if( $debe-$haber > 0)
											   {
											   $saldo_d = $debe-$haber;
											   $saldo_a = 0;
											   }
											   else
											   {
											   $saldo_d = 0;
											   $saldo_a = $haber-$debe;
											   }
								echo html_entity_decode($pg->codpgrupo.';'.$pg->descripcion.';;;;'.round($debe,2).';;'.round($haber,2).';;'.round($saldo_d,2).';;'.round($saldo_a,2)."\n");					   
											$tdebe += $debe;
											$thaber += $haber;
											$tsaldod += $saldo_d;
											$tsaldoa += $saldo_a;
									}				
												
								foreach($grupo as $g)
								{
									$epigrafe = $g->get_epigrafes();
									$debe = 0;
									$haber = 0;
					
									$auxt = $partida->totales_grupo($g->codgrupo, $fechaini, $fechafin);
											   $debe = round($auxt['debe'],2);
											   $haber = round($auxt['haber'],2);
									if( $debe!=0 OR $haber!=0)
									{
												if( $debe-$haber > 0)
											   {
											   $saldo_d = $debe-$haber;
											   $saldo_a = 0;
											   }
											   else
											   {
											   $saldo_d = 0;
											   $saldo_a = $haber-$debe;
											   }
							echo html_entity_decode($g->codgrupo.';  '.$g->descripcion.';;;;'.round($debe,2).';;'.round($haber,2).';;'.round($saldo_d,2).';;'.round($saldo_a,2)."\n");					   
						   
									}				
									
									foreach($epigrafe as $e)
									{
										$cuentas = $e->get_cuentas();
										$debe = 0;
										$haber = 0;
										$auxt = $partida->totales_epigrafe($e->codepigrafe, $fechaini, $fechafin);
											   $debe = round($auxt['debe'],2);
											   $haber = round($auxt['haber'],2);
										if( $debe!=0 OR $haber!=0)
										{
												if( $debe-$haber > 0)
											   {
											   $saldo_d = $debe-$haber;
											   $saldo_a = 0;
											   }
											   else
											   {
											   $saldo_d = 0;
											   $saldo_a = $haber-$debe;
											   }
							echo html_entity_decode($e->codepigrafe.';    '.$e->descripcion.';;;;'.round($debe,2).';;'.round($haber,2).';;'.round($saldo_d,2).';;'.round($saldo_a,2)."\n");
										
										}	
										
										foreach($cuentas as $c)
										{
										$subcuentas = $c->get_subcuentas();
										$debe = 0;
										$haber = 0;
										$auxt = $partida->totales_cuenta($c->codcuenta, $fechaini, $fechafin);
											   $debe = round($auxt['debe'],2);
											   $haber = round($auxt['haber'],2);
										if( $debe!=0 OR $haber!=0)
										{
												if( $debe-$haber > 0)
											   {
											   $saldo_d = $debe-$haber;
											   $saldo_a = 0;
											   }
											   else
											   {
											   $saldo_d = 0;
											   $saldo_a = $haber-$debe;
											   }
							echo html_entity_decode($c->codcuenta.';      '.$c->descripcion.';;;;'.round($debe,2).';;'.round($haber,2).';;'.round($saldo_d,2).';;'.round($saldo_a,2)."\n");				   
											
										}
											foreach($subcuentas as $sc)
											{
										$debe = 0;
										$haber = 0;
							
										$auxt = $partida->totales_subcuenta($sc->codsubcuenta, $fechaini, $fechafin);
											   $debe = round($auxt['debe'],2);
											   $haber = round($auxt['haber'],2);
										if( $debe!=0 OR $haber!=0)
													{
															if( $debe-$haber > 0)
														   {
														   $saldo_d = $debe-$haber;
														   $saldo_a = 0;
														   }
														   else
														   {
														   $saldo_d = 0;
														   $saldo_a = $haber-$debe;
														   }
						
							
							echo utf8_decode($sc->codsubcuenta.';        '.html_entity_decode($sc->descripcion).';;;;'.round($debe,2).';;'.round($haber,2).';;'.round($saldo_d,2).';;'.round($saldo_a,2)."\n");							   
														   
													}							   
											}	
										}					
									}				
								}
								
					
								$offset++;
							 }
					 ////////////////////////////////////////////
						echo "\n\n\n";	
						echo ';;Totales : ;;;'.round($tdebe,2).';;'.round($thaber,2).';;'.round($tsaldod,2).';;'.round($tsaldoa,2)."\n";
					   
					


         }
      }
   }

}
