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
 
 
require_model('empresa.php');
require_model('almacen.php');
require_model('articulo_proveedor.php');
require_model('asiento_factura.php');
require_model('fabricante.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('pedido_proveedor.php');
require_model('proveedor.php');
require_model('inventario.php');
require_model('albaran_proveedor.php');

class nueva_compra extends fs_controller
{
   public $empresa;
   public $agente;
   public $almacen;
   public $articulo;
   public $articulo_prov;
   public $divisa;
   public $fabricante;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $subcuentas;
   public $proveedor;
   public $proveedor_s;
   public $results;
   public $serie;
   public $tipo;
   public $verif_factura;
   public $verif_remito;
   public $numproveedor;
   public $cai;
   public $caivence;
   public $artsubcuentas;
   public $autorizar_factura;
   public $view_subcuen;
   public $view_subcuen_dev;
   public $id_factura;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'nueva compra', 'compras', FALSE, FALSE);
   }
   
   protected function private_core()
   {
   $this->ppage = $this->page->get('compras_albaranes');
   
	  $this->empresa = new empresa();   
      $this->articulo_prov = new articulo_proveedor();
      $this->fabricante = new fabricante();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->proveedor = new proveedor();
      $this->proveedor_s = FALSE;
      $this->results = array();
	  $factura= new factura_proveedor();
	  $this->verif_factura = $factura->all_sin_anular();
	  $remito= new albaran_proveedor();
	  $this->verif_remito = $remito->all_ptefactura();
	  $this->subcuentas = new subcuenta();
	  $this->artsubcuentas = new articulo();
	  $this->view_subcuen = $this->subcuentas->subcoenta_compras($this->empresa->codejercicio);
	  $this->view_subcuen_dev = $this->subcuentas->subcoenta_compras($this->empresa->codejercicio);  
	
	   
	  	
	  	
      
      if( isset($_REQUEST['tipo']) )
      {
 
	  	 	if(isset($_REQUEST['autorizar_factura']) )
            {
			$this->autorizar_factura= $_REQUEST['autorizar_factura'];
			} 
         $this->tipo = $_REQUEST['tipo'];

      }
      else
      {
         foreach($this->tipos_a_guardar() as $t)
         {
            $this->tipo = $t['tipo'];
            break;
         }
      }
	  

      
      if( isset($_REQUEST['buscar_proveedor']) )
      {
         $this->buscar_proveedor();
      }
      else if( isset($_REQUEST['datosproveedor']) )
      {
         $this->datos_proveedor();
      }
      else if( isset($_REQUEST['new_articulo']) )
      {
         $this->new_articulo();
      }
      else if($this->query != '')
      {
         $this->new_search();
      }
      else if( isset($_POST['referencia4precios']) )
      {
         $this->get_precios_articulo();
      }
      else if( isset($_POST['proveedor']) )
      {
		if( isset($_POST['numfactproveedor']) )$this->numproveedor = $_POST['numfactproveedor'];		
         $this->proveedor_s = $this->proveedor->get($_POST['proveedor']);
        $this->cai = $this->proveedor_s->cai;
		$this->caivence = $this->proveedor_s->caivence;
         if( isset($_POST['nuevo_proveedor']) )
         {
            if($_POST['nuevo_proveedor'] != '')
            {
               $this->proveedor_s = FALSE;
               if($_POST['nuevo_dni'] != '')
               {
                  $this->proveedor_s = $this->proveedor->get_by_cifnif($_POST['nuevo_dni']);
                  if($this->proveedor_s)
                  {
                     $this->new_advice('Ya existe un proveedor con ese '.FS_CIFNIF.'. Se ha seleccionado.');
                  }
               }
               
               if(!$this->proveedor_s)
               {
                  $this->proveedor_s = new proveedor();
                  $this->proveedor_s->codproveedor = $this->proveedor_s->get_new_codigo();
                  $this->proveedor_s->nombre = $this->proveedor_s->razonsocial = $_POST['nuevo_proveedor'];
                  $this->proveedor_s->cifnif = $_POST['nuevo_dni'];
                  $this->proveedor_s->acreedor = isset($_POST['acreedor']);
                  $this->proveedor_s->save();
               }
            }
         }
         
         if( isset($_POST['codagente']) )
         {
            $agente = new agente();
            $this->agente = $agente->get($_POST['codagente']);
         }
         else
            $this->agente = $this->user->get_agente();
         
         $this->almacen = new almacen();
         $this->serie = new serie();
         $this->forma_pago = new forma_pago();
         $this->divisa = new divisa();
         
         if( isset($_POST['tipo']) )
         {
            if($_POST['tipo'] == 'pedido')
            {
               if( class_exists('pedido_proveedor') )
               {
                  $this->nuevo_pedido_proveedor();
               }
               else
                  $this->new_error_msg('Clase pedido_proveedor no encontrada.');
            }
            else if($_POST['pagina'] == 'albaran')
            {
				
               $this->nuevo_albaran_proveedor();
			   
//			   header('Location: '.$this->url_list1());
			   
            }
            else if($_POST['tipo'] == 'B')
            {
			
			 $this->nueva_factura_proveedor();
//			header('Location: '.$this->url_list());
			 
            }
            else if($_POST['tipo'] == 'F')
            {			
			 $this->nueva_factura_proveedor();
//			header('Location: '.$this->url_list());			 
            }	
            else if($_POST['tipo'] == 'T')
            {			
			 $this->nueva_factura_proveedor();
//			header('Location: '.$this->url_list());			 
            }		
			 else if($_POST['tipo'] == 'Q')
            {			
			 $this->nueva_factura_proveedor();
//			header('Location: '.$this->url_list());			 
            }			
			 else if($_POST['tipo'] == 'C')
            {
			  $this->nueva_factura_proveedor();
//			  header('Location: '.$this->url_list());
            }
			 else if($_POST['tipo'] == 'D')
            {
			  $this->nueva_factura_proveedor();
//			  header('Location: '.$this->url_list());
            }
         }
      }
   }
   
   /**
    * Devuelve los tipos de documentos a guardar,
    * así para añadir tipos no hay que tocar la vista.
    * @return type
    */
   public function tipos_a_guardar()
   {
      $tipos = array();
      
      if( $this->user->have_access_to('compras_pedido') AND class_exists('pedido_proveedor') )
      {
         $tipos[] = array('tipo' => 'pedido', 'nombre' => 'Pedido a proveedor');
      }
      
      if( $this->user->have_access_to('compras_albaran') )
      {
         $tipos[] = array('tipo' => 'albaran', 'nombre' => ucfirst(FS_ALBARAN).' de proveedor');
      }
      
      if( $this->user->have_access_to('compras_factura') )
      {
         $tipos[] = array('tipo' => 'factura', 'nombre' => 'Ingreso proveedor');
      }
      
      return $tipos;
   }
   
   public function url()
   {
      return 'index.php?page='.__CLASS__.'&tipo='.$this->tipo.'&autorizar_factura';
   }
   
    public function url_list()
   {
      return 'index.php?page=compras_facturas';
   }
   
    public function url_list1()
   {
      return 'index.php?page=compras_albaranes';
   }   
   
   public function url_retorno()
   {

         return 'index.php?page=compras_facturas';
   }
   
      public function url_factura()
   {

         return 'index.php?page=compras_factura&id='.$this->id_factura;
   }
   
   private function buscar_proveedor()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $json = array();
      foreach($this->proveedor->search($_REQUEST['buscar_proveedor']) as $pro)
      {
         $json[] = array('value' => $pro->razonsocial, 'data' => $pro->codproveedor);
      }
      
      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_proveedor'], 'suggestions' => $json) );
   }
   
   private function datos_proveedor()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      header('Content-Type: application/json');
      echo json_encode( $this->proveedor->get($_REQUEST['datosproveedor']) );
   }
   
   private function new_articulo()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $art0 = new articulo();
      $art0->referencia = $_REQUEST['referencia'];
      if( $art0->exists() )
      {
         $this->results[] = $art0->get($_REQUEST['referencia']);
      }
      else
      {
         $art0->descripcion = $_REQUEST['descripcion'];
         $art0->set_impuesto($_REQUEST['codimpuesto']);
         $art0->set_pvp( floatval($_REQUEST['pvp']) );
         $art0->costemedio = floatval($_REQUEST['coste']);
         $art0->preciocoste = floatval($_REQUEST['coste']);
         $art0->publico = isset($_POST['publico']);
         
         if($_POST['codfamilia'] != '')
         {
            $art0->codfamilia = $_REQUEST['codfamilia'];
         }
         
         if($_POST['codfabricante'] != '')
         {
            $art0->codfabricante = $_REQUEST['codfabricante'];
         }
         
         if($_POST['refproveedor'] != '' AND $_POST['refproveedor'] != $_POST['referencia'])
         {
            $art0->equivalencia = $_POST['refproveedor'];
         }
         
         if( $art0->save() )
         {
            $art0->coste = floatval($_POST['coste']);
            $art0->dtopor = 0;
            
            /// buscamos y guardamos el artículo del proveedor
            $ap = $this->articulo_prov->get_by($art0->referencia, $_POST['codproveedor'], $_POST['refproveedor']);
            if($ap)
            {
               $art0->coste = $ap->precio;
               $art0->dtopor = $ap->dto;
            }
            else
            {
               $ap = new articulo_proveedor();
               $ap->codproveedor = $_POST['codproveedor'];
            }
            $ap->referencia = $art0->referencia;
            $ap->refproveedor = $_POST['refproveedor'];
            $ap->descripcion = $art0->descripcion;
            $ap->codimpuesto = $art0->codimpuesto;
            $ap->precio = floatval($_POST['coste']);
            $ap->save();
            
            $this->results[] = $art0;
         }
      }
      
      header('Content-Type: application/json');
      echo json_encode($this->results);
   }
   
   private function new_search()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $this->results = $this->search_from_proveedor();
      
      /// completamos los datos
      foreach($this->results as $i => $value)
      {
         $this->results[$i]->query = $this->query;
         $this->results[$i]->coste = $value->preciocoste();
         $this->results[$i]->dtopor = 0;
         
         if( isset($_REQUEST['codproveedor']) )
         {
            $ap = $this->articulo_prov->get_by($value->referencia, $_REQUEST['codproveedor']);
            if($ap)
            {
               $this->results[$i]->coste = $ap->precio;
               $this->results[$i]->dtopor = $ap->dto;
            }
         }
      }
      
      header('Content-Type: application/json');
      echo json_encode($this->results);
   }
   
   private function get_precios_articulo()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/nueva_compra_precios';
      
      $articulo = new articulo();
      $this->articulo = $articulo->get($_POST['referencia4precios']);
   }
   
   private function nuevo_pedido_proveedor()
   {
      $continuar = TRUE;
      
      $proveedor = $this->proveedor->get($_POST['proveedor']);
      if( $proveedor )
         $this->save_codproveedor( $proveedor->codproveedor );
      else
      {
         $this->new_error_msg('Proveedor no encontrado.');
         $continuar = FALSE;
      }
      
      if(isset($_POST['almacen']))
	  {
		  $almacen = $this->almacen->get($_POST['almacen']);
		  if( $almacen )
			 $this->save_codalmacen( $almacen->codalmacen );
		  else
		  {
			 $this->new_error_msg('Almacén no encontrado.');
			 $continuar = FALSE;
		  }
		}
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $pedido = new pedido_proveedor();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$pedido->url().'">'.FS_PEDIDOS.'</a>
               para ver si el '.FS_PEDIDO.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $pedido->fecha = $_POST['fecha'];
         $pedido->hora = $_POST['hora'];
         $pedido->codproveedor = $proveedor->codproveedor;
         $pedido->nombre = $proveedor->nombre;
         $pedido->cifnif = $proveedor->cifnif;
         $pedido->codalmacen = $almacen->codalmacen;
         $pedido->codejercicio = $ejercicio->codejercicio;
         $pedido->codserie = $serie->codserie;
         $pedido->codpago = $forma_pago->codpago;
         $pedido->coddivisa = $divisa->coddivisa;
         $pedido->tasaconv = $divisa->tasaconv_compra;
         
         if($_POST['tasaconv'] != '')
         {
            $pedido->tasaconv = floatval($_POST['tasaconv']);
         }
         
         $pedido->codagente = $this->agente->codagente;
         $pedido->numproveedor = $_POST['numproveedor'];
         $pedido->observaciones = $_POST['observaciones'];
         $pedido->irpf = $serie->irpf;
         
         if( $pedido->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i < $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $linea = new linea_pedido_proveedor();
                  $linea->idpedido = $pedido->idpedido;
                  $linea->descripcion = $_POST['desc_'.$i];
                  
                  if( !$serie->siniva AND $proveedor->regimeniva != 'Exento' )
                  {
                     $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                     if($imp0)
                     {
                        $linea->codimpuesto = $imp0->codimpuesto;
                        $linea->iva = floatval($_POST['iva_'.$i]);
                        $linea->recargo = floatval($_POST['recargo_'.$i]);
                     }
                     else
                     {
                        $linea->iva = floatval($_POST['iva_'.$i]);
                        $linea->recargo = floatval($_POST['recargo_'.$i]);
                     }
                  }
                  
                  $linea->irpf = floatval($_POST['irpf_'.$i]);
                  $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                  $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                  $linea->dtopor = floatval($_POST['dto_'.$i]);
                  $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                  $linea->pvptotal = floatval($_POST['neto_'.$i]);
                  
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea->referencia = $articulo->referencia;
                  }
                  
                  if( $linea->save() )
                  {
                     if($articulo)
                     {
                        if( isset($_POST['costemedio']) )
                        {
                           if($articulo->costemedio == 0)
                           {
                              $articulo->costemedio = $linea->pvptotal/$linea->cantidad;
                           }
                           else
                           {
                              $articulo->costemedio = $articulo->get_costemedio();
                              if($articulo->costemedio == 0)
                              {
                                 $articulo->costemedio = $linea->pvptotal/$linea->cantidad;
                              }
                           }
                           
                           $articulo->save();
                           $this->actualizar_precio_proveedor($pedido->codproveedor, $linea);
                        }
                     }
                     
                     $pedido->neto += $linea->pvptotal;
                     $pedido->totaliva += ($linea->pvptotal * $linea->iva/100);
                     $pedido->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                     $pedido->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
                  }
                  else
                  {
                     $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $pedido->neto = round($pedido->neto, FS_NF0);
               $pedido->totaliva = round($pedido->totaliva, FS_NF0);
               $pedido->totalirpf = round($pedido->totalirpf, FS_NF0);
               $pedido->totalrecargo = round($pedido->totalrecargo, FS_NF0);
               $pedido->total = $pedido->neto + $pedido->totaliva - $pedido->totalirpf + $pedido->totalrecargo;
               
               if( abs(floatval($_POST['atotal']) - $pedido->total) >= .02 )
               {
                  $this->new_error_msg("El total difiere entre la vista y el controlador (".
                          $_POST['atotal']." frente a ".$pedido->total."). Debes informar del error.");
                  $pedido->delete();
               }
               else if( $pedido->save() )
               {
                  $this->new_message("<a href='".$pedido->url()."'>".ucfirst(FS_PEDIDO)."</a> guardado correctamente.");
                  $this->new_change(ucfirst(FS_PEDIDO).' Proveedor '.$pedido->codigo, $pedido->url(), TRUE);
                  
                  if($_POST['redir'] == 'TRUE')
                  {
                     header('Location: '.$pedido->url());
                  }
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$pedido->url()."'>".FS_PEDIDO."</a>!");
            }
            else if( $pedido->delete() )
            {
               $this->new_message(ucfirst(FS_PEDIDO)." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$pedido->url()."'>".FS_PEDIDO."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_PEDIDO."!");
      }
   }
///////////////////////////////////////////////////
/////////////////  ALBARAN
//////////////////////////////////////////////////   
   private function nuevo_albaran_proveedor()
   {
      $continuar = TRUE;
      
      $proveedor = $this->proveedor->get($_POST['proveedor']);
      if( $proveedor )
         $this->save_codproveedor( $proveedor->codproveedor );
      else
      {
         $this->new_error_msg('Proveedor no encontrado.');
         $continuar = FALSE;
      }
     
	 if(isset($_POST['almacen']))
	  { 
		  $almacen = $this->almacen->get($_POST['almacen']);
		  if( $almacen )
			 $this->save_codalmacen( $almacen->codalmacen );
		  else
		  {
			 $this->new_error_msg('Almacén no encontrado.');
			 $continuar = FALSE;
		  }
      }
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
		 header('Location: '.$this->url_list1().'&nueva=2');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $albaran = new albaran_proveedor();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $albaran->fecha = $_POST['fecha'];
         $albaran->hora = $_POST['hora'];
         $albaran->codproveedor = $proveedor->codproveedor;
         $albaran->nombre = $proveedor->razonsocial;
         $albaran->cifnif = $proveedor->cifnif;
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv_compra;
         
         if($_POST['tasaconv'] != '')
         {
            $albaran->tasaconv = floatval($_POST['tasaconv']);
         }
         $albaran->codagente = $this->agente->codagente;
         $albaran->numremito = $_POST['tipo'].'/'.$_POST['numproveedor'];
		 $albaran->tipo = $_POST['tipo'];
         $albaran->observaciones = $_POST['observaciones'];
         $albaran->irpf = $serie->irpf;
		 $albaran->cai = $_POST['cai'];
         $albaran->caivence = $_POST['caivence'];
         
         if( $albaran->save() )
         {
            $art0 = new articulo();
			$inventario = new inventario();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i < $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $linea = new linea_albaran_proveedor();
                  $linea->idalbaran = $albaran->idalbaran;
                  $linea->descripcion = $_POST['desc_'.$i];
                  
 /*                 if( !$serie->siniva AND $proveedor->regimeniva != 'Exento' )
                  {
                     $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                     if($imp0)
                     {
                        $linea->codimpuesto = $imp0->codimpuesto;
                        $linea->iva = floatval($_POST['iva_'.$i]);
                        $linea->recargo = floatval($_POST['recargo_'.$i]);
                     }
                     else
                     {
                        $linea->iva = floatval($_POST['iva_'.$i]);
                        $linea->recargo = floatval($_POST['recargo_'.$i]);
                     }
                  }
 */                 
                  $linea->irpf = floatval($_POST['irpf_'.$i]);
                  $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
		
				  $postot = strlen($_POST['subcuenta_'.$i]);				  
				  $poscad = strpos($_POST['subcuenta_'.$i], '/');
				  $posid = strpos($_POST['subcuenta_'.$i], '%');				  				  
				  $subcuencod = substr($_POST['subcuenta_'.$i], 0, $poscad);
				  $subcuendes = substr($_POST['subcuenta_'.$i],$poscad+1,$posid-$postot);
				  $idsubcuen = substr($_POST['subcuenta_'.$i],$posid+1);
				  $linea->codsubcuenta = $subcuencod;
				  $linea->idsubcuenta = $idsubcuen;
				  $linea->subcuentadesc = $subcuendes;
				  
                  $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                  $linea->dtopor = floatval($_POST['dto_'.$i]);
                  $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                  $linea->pvptotal = floatval($_POST['neto_'.$i]);
                     
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea->referencia = $articulo->referencia;
                  }
                  
                  if( $linea->save() )
                  {
                     if($articulo)
                     {
                        if( isset($_POST['stock']) )
                        {
                           $articulo->sum_stock($albaran->codalmacen, $linea->cantidad, isset($_POST['costemedio']) );
						   if($articulo) $inventario->inventario_agregar( $albaran->codalmacen,$linea->referencia,$linea->cantidad,$linea->pvpunitario);
                        }
                        else if( isset($_POST['costemedio']) )
                        {
                           $articulo->stockfis += $linea->cantidad;
                           $articulo->costemedio = $articulo->get_costemedio();
                           $articulo->stockfis -= $linea->cantidad;
                           $articulo->save();
                        }
                        
                        if( isset($_POST['costemedio']) )
                        {
                           $this->actualizar_precio_proveedor($albaran->codproveedor, $linea);
                        }
                     }
                     
                     $albaran->neto += $linea->pvptotal;
                     $albaran->totaliva += ($linea->pvptotal * $linea->iva/100);
                     $albaran->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                     $albaran->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
                  }
                  else
                  {
                     $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $albaran->neto = round($albaran->neto, FS_NF0);
               $albaran->totaliva = round($albaran->totaliva, FS_NF0);
               $albaran->totalirpf = round($albaran->totalirpf, FS_NF0);
               $albaran->totalrecargo = round($albaran->totalrecargo, FS_NF0);
               $albaran->total = $albaran->neto + $albaran->totaliva - $albaran->totalirpf + $albaran->totalrecargo;
               
               if( abs(floatval($_POST['atotal']) - $albaran->total) >= .02 )
               {
                  $this->new_error_msg("El total difiere entre la vista y el controlador (".
                          $_POST['atotal']." frente a ".$albaran->total."). Debes informar del error.");
                  $albaran->delete();
               }
               else if( $albaran->save() )
               {
                  $this->new_message("<a href='".$albaran->url()."'>".ucfirst(FS_ALBARAN)."</a> guardado correctamente.");
                  $this->new_change(ucfirst(FS_ALBARAN).' Proveedor '.$albaran->codigo, $albaran->url(), TRUE);
                  
                  if($_POST['redir'] == 'TRUE')
                  {
   //                  header('Location: '.$albaran->url());
   	//					header('Location: '.$this->url_list1().'&nueva=1');
                  }
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $albaran->delete() )
            {
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
      }
   }
   
   
   
 //////////////////////////////////////////////////////////////////  
 ///////////////  Genera FACTURA  ORDEN DEBITO  ORDEN CREDITO
   private function nueva_factura_proveedor()
   {
      $continuar = TRUE;
      
      $proveedor = $this->proveedor->get($_POST['proveedor']);
      if( $proveedor )
         $this->save_codproveedor( $proveedor->codproveedor );
      else
      {
         $this->new_error_msg('Proveedor no encontrado.');
         $continuar = FALSE;
      }
      
	  if(isset($_POST['almacen']))
	  {
		  $almacen = $this->almacen->get($_POST['almacen']);
		  if( $almacen )
			 $this->save_codalmacen( $almacen->codalmacen );
		  else
		  {
			 $this->new_error_msg('Almacén no encontrado.');
			 $continuar = FALSE;
		  }
		 } 
		 
      $factura = new factura_proveedor();
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
	  	$this->autorizar_factura=1;
   //      $this->new_error_msg('Ejercicio no encontrado.1');
		  header('Location: '.$factura->url_list().'&nueva=2');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie )
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$factura->url().'">Facturas</a>
               para ver si la factura se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
	  
	  $a = strlen(substr($_POST['fecha'],6));
	  if( $a != 4) $continuar = FALSE;

      if( $continuar )
      {
         $factura->fecha = $_POST['fecha'];
		 $fecha_factura = $_POST['fecha'];
         $factura->hora = $_POST['hora'];
         $factura->codproveedor = $proveedor->codproveedor;
         $factura->nombre = $proveedor->razonsocial;
		 $factura->idpagodevol=0;
         $factura->cifnif = $proveedor->cifnif;
         $factura->codalmacen = $almacen->codalmacen;
         $factura->codejercicio = $ejercicio->codejercicio;
         $factura->codserie = $serie->codserie;
         $factura->codpago = $forma_pago->codpago;
         $factura->coddivisa = $divisa->coddivisa;
         $factura->tasaconv = $divisa->tasaconv_compra;
		 $factura->tipo = $_POST['tipo'];
		 $factura->cai = $_POST['cai'];
         $factura->caivence = $_POST['caivence'];
		 
         if($_POST['tasaconv'] != '')
         {
            $factura->tasaconv = floatval($_POST['tasaconv']);
         }
         
         $factura->codagente = $this->agente->codagente;
         $factura->numproveedor = $_POST['tipo'].'/'.$_POST['numproveedor'];
         $factura->observaciones = $_POST['observaciones'];
         $factura->irpf = $serie->irpf;
         
         if($forma_pago->genrecibos == 'Pagados')
         {
//            $factura->pagada = TRUE;
         }
         
         if( $factura->save() )
         {
            $art0 = new articulo();
			$inventario = new inventario();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i < $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $linea = new linea_factura_proveedor();
                  $linea->idfactura = $factura->idfactura;
				  $this->id_factura = $factura->idfactura;
                  $linea->descripcion = $_POST['desc_'.$i];                     
                  $linea->irpf = floatval($_POST['irpf_'.$i]);
                  $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                  $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                  $linea->dtopor = floatval($_POST['dto_'.$i]);
                  $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                  $linea->pvptotal = floatval($_POST['neto_'.$i]);

				  $postot = strlen($_POST['subcuenta_'.$i]);				  
				  $poscad = strpos($_POST['subcuenta_'.$i], '/');
				  $posid = strpos($_POST['subcuenta_'.$i], '%');				  				  
				  $subcuencod = substr($_POST['subcuenta_'.$i], 0, $poscad);
				  $subcuendes = substr($_POST['subcuenta_'.$i],$poscad+1,$posid-$postot);
				  $idsubcuen = substr($_POST['subcuenta_'.$i],$posid+1);	  
				  $linea->codsubcuenta = $subcuencod;				  				  
                  $linea->subcuentadesc = $subcuendes;
				  $linea->idsubcuenta = $idsubcuen;
                  $articulo = $art0->get($_POST['referencia_'.$i]);
				  
				  ////////////////////////////////////////////////////////////////////////
				  ////  GUARDA subcuenta en articulo cuando se carga la factura
				  ////////////////////////////////////////////////////
				  $artval = $this->artsubcuentas->get_ref($_POST['referencia_'.$i]);


				  if($artval != $subcuencod || $subcuencod==NULL )
				  {
				  if($_POST['tipo'] == 'B' || $_POST['tipo'] == 'F' || $_POST['tipo'] == 'T' || $_POST['tipo'] == 'Q' || $_POST['tipo'] == 'C' || $_POST['tipo'] == 'D' )
            		{				  
					$this->artsubcuentas->guarda_subcuenta_comp($_POST['referencia_'.$i],$subcuencod,$subcuendes);
					}
				   else $this->artsubcuentas->guarda_subcuenta_dev($_POST['referencia_'.$i],$subcuencod,$subcuendes);
					
					
				  }
				//////////////////////////////////////////////////////////////////
				////////////////////////////////////////////////////////////////
				
                  if($articulo)
                  {
                     $linea->referencia = $articulo->referencia;
                  }
                  
                  if( $linea->save() )
                  {
                     if($articulo)
                     {
                        if( isset($_POST['costemedio']) )
                        {
                           if($articulo->costemedio == 0)
                           {
                              $articulo->costemedio = $linea->pvptotal/$linea->cantidad;
                           }
                           else
                           {
                              $articulo->costemedio = $articulo->get_costemedio();
                              if($articulo->costemedio == 0)
                              {
                                 $articulo->costemedio = $linea->pvptotal/$linea->cantidad;
                              }
                           }
                           
                           $this->actualizar_precio_proveedor($factura->codproveedor, $linea);
                        }
						
                        if( isset($_POST['stock']) )
                        {
						
                           $articulo->sum_stock($factura->codalmacen, $linea->cantidad);
						   if($articulo) $inventario->inventario_agregar( $factura->codalmacen,$linea->referencia,$linea->cantidad,$linea->pvpunitario);
                        }
                        else if( isset($_POST['costemedio']) )
                        {
                           $articulo->save();
                        }
                     }
                     
                     $factura->neto += $linea->pvptotal;
                     $factura->totaliva += ($linea->pvptotal * $linea->iva/100);
                     $factura->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                     $factura->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
                  }
                  else
                  {
                     $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                     $continuar = FALSE;
                  }
               }
            }
           
            if($continuar)
            {

				$factura->fecha = $fecha_factura;
               /// redondeamos			   
               $factura->neto = round($factura->neto, FS_NF0);
               $factura->totaliva = round($factura->totaliva, FS_NF0);
               $factura->totalirpf = round($factura->totalirpf, FS_NF0);
               $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
               $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;
               
               if( abs(floatval($_POST['atotal']) - $factura->total) >= .02 )
               {
                  $this->new_error_msg("El total difiere entre el controlador y la vista (".
                          $factura->total." frente a ".$_POST['atotal']."). Debes informar del error.");
                  $factura->delete();
               }
               else if( $factura->save() )
               {
///////// GENERA  ASIENTO			   
//                  $this->generar_asiento($factura);
//                  $this->new_message("<a href='".$factura->url()."'>Factura</a> guardada correctamente.");
     //             $this->new_change('Factura Proveedor '.$factura->codigo, $factura->url(), TRUE);
	 				$this->autorizar_factura=1;
                   $this->new_error_msg('Factura Guardada');
                  if($_POST['redir'] == 'TRUE')
                  {
                    header('Location: '.$factura->url_list().'&nueva=1');

                  }
               }
               else $this->new_error_msg("¡Imposible actualizar la <a href='".$factura->url()."'>factura</a>!");
            }
            else 
            {
               $this->new_message("Factura eliminada correctamente.");
            }
            
         }
         else
            $this->new_error_msg("¡Imposible guardar la factura!");
      }
	  
   }
   
   private function generar_asiento($factura)
   {
      if($this->empresa->contintegrada)
      {
         $asiento_factura = new asiento_factura();
         $asiento_factura->generar_asiento_compra($factura);
         
         foreach($asiento_factura->errors as $err)
         {
            $this->new_error_msg($err);
         }
         
         foreach($asiento_factura->messages as $msg)
         {
            $this->new_message($msg);
         }
      }
   }
   
   private function actualizar_precio_proveedor($codproveedor, $linea)
   {
      if( !is_null($linea->referencia) )
      {
         $artp = $this->articulo_prov->get_by($linea->referencia, $codproveedor);
         if(!$artp)
         {
            $artp = new articulo_proveedor();
            $artp->codproveedor = $codproveedor;
            $artp->referencia = $linea->referencia;
            $artp->refproveedor = $linea->referencia;
            $artp->codimpuesto = $linea->codimpuesto;
            $artp->descripcion = $linea->descripcion;
         }
         
         $artp->precio = $linea->pvpunitario;
         $artp->dto = $linea->dtopor;
         $artp->save();
      }
   }
   
   private function search_from_proveedor()
   {
      $artilist = array();
      $query = $this->articulo_prov->no_html( strtolower($this->query) );
      $sql = "SELECT * FROM articulos";
      $separador = ' WHERE';
      
      if($_REQUEST['codfamilia'] != '')
      {
         $sql .= $separador." codfamilia = ".$this->articulo_prov->var2str($_REQUEST['codfamilia']);
         $separador = ' AND';
      }
      
      if($_REQUEST['codfabricante'] != '')
      {
         $sql .= $separador." codfabricante = ".$this->articulo_prov->var2str($_REQUEST['codfabricante']);
         $separador = ' AND';
      }
      
      if( isset($_REQUEST['con_stock']) )
      {
         $sql .= $separador." stockfis > 0";
         $separador = ' AND';
      }
      
      if( isset($_REQUEST['solo_proveedor']) AND isset($_REQUEST['codproveedor']) )
      {
         $sql .= $separador." referencia IN (SELECT referencia FROM articulosprov WHERE codproveedor = "
                 .$this->articulo_prov->var2str($_REQUEST['codproveedor']).")";
         $separador = ' AND';
      }
      
      if( is_numeric($this->query) )
      {
         $sql .= $separador." (lower(referencia) = ".$this->articulo_prov->var2str($this->query)
                 . " OR referencia LIKE '%".$this->query."%' OR equivalencia LIKE '%".$this->query."%'"
                 . " OR descripcion LIKE '%".$this->query."%' OR codbarras = '".$this->query."')";
      }
      else
      {
         $buscar = str_replace(' ', '%', $this->query);
         $sql .= $separador." (lower(referencia) = ".$this->articulo_prov->var2str($this->query)
                 . " OR lower(referencia) LIKE '%".$buscar."%' OR lower(equivalencia) LIKE '%".$buscar."%'"
                 . " OR lower(descripcion) LIKE '%".$buscar."%')";
      }
      
      $sql .= " ORDER BY referencia ASC";
      
      $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, 0);
      if($data)
      {
         foreach($data as $a)
         {
            $artilist[] = new articulo($a);
         }
      }
      
      return $artilist;
   }
}
