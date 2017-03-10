<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('cuenta.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('partida.php');

/**
 * El cuarto nivel de un plan contable. Está relacionada con una única cuenta.
 */
class subcuenta extends fs_model
{
   /**
    * Clave primaria.
    * @var type 
    */
   public $idsubcuenta;
   
   public $codsubcuenta;
   
   /**
    * ID de la cuenta a la que pertenece.
    * @var type 
    */
   public $idcuenta;
   
   public $codcuenta;
   
   public $codejercicio;
   
   public $coddivisa;
   
   public $codimpuesto;
   
   public $descripcion;
   
   public $haber;
   
   public $debe;
   
   public $saldo;
   
   public $recargo;
   
   public $iva;
   public $alias;
   
   public function __construct($s=FALSE)
   {

      parent::__construct('co_subcuentas', 'plugins/facturacion_base/');
      if($s)
      {
         $this->idsubcuenta = $this->intval($s['idsubcuenta']);
         $this->codsubcuenta = $s['codsubcuenta'];
         $this->idcuenta = $this->intval($s['idcuenta']);
         $this->codcuenta = $s['codcuenta'];
         $this->codejercicio = $s['codejercicio'];
         $this->coddivisa = $s['coddivisa'];
         $this->codimpuesto = $s['codimpuesto'];
         $this->descripcion = $s['descripcion'];
         $this->debe = floatval($s['debe']);
         $this->haber = floatval($s['haber']);
         $this->saldo = floatval($s['saldo']);
         $this->recargo = floatval($s['recargo']);
         $this->iva = floatval($s['iva']);
		 $this->alias = $s['alias'];
 
      }
      else
      {
         $this->idsubcuenta = NULL;
         $this->codsubcuenta = NULL;
         $this->idcuenta = NULL;
         $this->codcuenta = NULL;
         $this->codejercicio = NULL;
         $this->coddivisa = $this->default_items->coddivisa();
         $this->codimpuesto = NULL;
         $this->descripcion = '';
         $this->debe = 0;
         $this->haber = 0;
         $this->saldo = 0;
         $this->recargo = 0;
         $this->iva = 0;
		 $this->alias = NULL;
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      
      /// eliminamos todos los PDFs relacionados
      if( file_exists('tmp/'.FS_TMP_NAME.'libro_mayor') )
      {
         foreach(glob('tmp/'.FS_TMP_NAME.'libro_mayor/*') as $file)
         {
            if( is_file($file) )
               unlink($file);
         }
      }
      if( file_exists('tmp/'.FS_TMP_NAME.'libro_diario') )
      {
         foreach(glob('tmp/'.FS_TMP_NAME.'libro_diario/*') as $file)
         {
            if( is_file($file) )
               unlink($file);
         }
      }
      if( file_exists('tmp/'.FS_TMP_NAME.'inventarios_balances') )
      {
         foreach(glob('tmp/'.FS_TMP_NAME.'inventarios_balances/*') as $file)
         {
            if( is_file($file) )
               unlink($file);
         }
      }
      
      /// forzamos la creación de la tabla de cuentas
      $cuenta = new cuenta();
      return '';
   }
   
   public function get_descripcion_64()
   {
      return base64_encode($this->descripcion);
   }
   
   public function tasaconv()
   {
      if( isset($this->coddivisa) )
      {
         $divisa = new divisa();
         $div0 = $divisa->get($this->coddivisa);
         if($div0)
         {
            return $div0->tasaconv;
         }
         else
            return 1;
      }
      else
         return 1;
   }
   
   public function url()
   {
      if( is_null($this->idsubcuenta) )
      {
         return 'index.php?page=contabilidad_cuentas';
      }
      else
         return 'index.php?page=contabilidad_subcuenta&id='.$this->idsubcuenta;
   }
   
   
   public function get_cuenta()
   {
      $cuenta = new cuenta();
      return $cuenta->get($this->idcuenta);
   }
   
     public function get_alias()
   {
      $cuenta = new cuenta();
      return $cuenta->get($this->idcuenta);
   }
   
   public function get_ejercicio()
   {
      $eje = new ejercicio();
      return $eje->get($this->codejercicio);
   }
   
   public function get_partidas($offset=0)
   {
      $part = new partida();
      return $part->all_from_subcuenta($this->idsubcuenta, $offset);
   }
   
      public function get_partidas_desc($offset=0)
   {
      $part = new partida();
      return $part->all_from_subcuenta_desc($this->idsubcuenta, $offset);
   }
   
   public function get_partidas_libros($mes,$offset=0)
   {
      $part = new partida();
	  $saldo_ant = $this->get_partidas_saldo_anterior();
      return $part->libro_subcuenta($this->idsubcuenta,$mes,$saldo_ant, $offset);
   }
   
      public function get_partidas_libros_nomayor($offset=0)
   {
      $part = new partida();
	  $saldo_ant = $this->get_partidas_saldo_anterior();
      return $part->libro_subcuenta_nomayor($this->idsubcuenta,$saldo_ant, $offset);
   }
      public function get_partidas_libros_total($mes)
   {
      $part = new partida();
	  $saldo_ant = $this->get_partidas_saldo_anterior();
      return $part->libro_subcuenta_total($this->idsubcuenta,$mes,$saldo_ant);
   }
   
   public function get_partidas_libros_ver($mes,$codejercicio,$offset=0)
   {
      $part = new partida();
	  $saldo_ant = $this->get_partidas_saldo_anterior_ver($mes,$codejercicio);
      return $part->libro_subcuenta_ver($this->idsubcuenta,$mes,$saldo_ant,$codejercicio, $offset);
   }
   
   public function get_partidas_mes($offset=0)
   {
      $part = new partida();
      return $part->libro_mes_subcuenta($this->idsubcuenta, $offset);
   }
   
   public function meses_archivo($offset=0)
   {
      $part = new partida();
      return $part->meses_archivo($this->idsubcuenta, $offset);
   }
   
    public function get_partidas_saldo_anterior()
   {
      $part = new partida();
      return $part->libro_saldo_anterior_subcuenta($this->idsubcuenta);
   }
   
    public function get_partidas_saldo_anterior_ver($mes,$codejercicio)
   {
      $part = new partida();
      return $part->libro_saldo_anterior_subcuenta_ver($this->idsubcuenta,$mes,$codejercicio);
   }
   
   public function get_partidas_full()
   {
      $part = new partida();
      return $part->full_from_subcuenta($this->idsubcuenta);
   }
   
   public function count_partidas()
   {
      $part = new partida();
      return $part->count_from_subcuenta($this->idsubcuenta);
   }
   
   public function get_totales()
   {
      $part = new partida();
      return $part->totales_from_subcuenta( $this->idsubcuenta );
   }
   
      public function get_nom_mes($mes)
   {
   		$tipo = '';
      switch ($mes) {
			case '01':
				$tipo = 'ENERO';
				break;
			case '02':
				$tipo = 'FEBRERO';
				break;
			case '03':
				$tipo = 'MARZO';
				break;	
			case '04':
				$tipo = 'ABRIL';
				break;	
			case '05':
				$tipo = 'MAYO';
				break;				
			case '06':
				$tipo = 'JUNIO';
				break;
			case '07':
				$tipo = 'JULIO';
				break;
			case '08':
				$tipo = 'AGOSTO';
				break;	
			case '09':
				$tipo = 'SEPTIEMBRE';
				break;	
			case '10':
				$tipo = 'OCTUBRE';
				break;				
			case '11':
				$tipo = 'NOVIEMBRE';
				break;
			case '12':
				$tipo = 'DICIEMBRE';
				break;					
					}
		return $tipo;			
   }
   
   public function get($id)
   {
      $subc = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idsubcuenta = ".$this->var2str($id).";");
      if($subc)
      {
         return new subcuenta($subc[0]);
      }
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod, $ejercicio, $crear=FALSE)
   {
      $subc = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codsubcuenta = ".$this->var2str($cod).
              " AND codejercicio = ".$this->var2str($ejercicio).";");
      if($subc)
      {
         return new subcuenta($subc[0]);
      }
      else if($crear)
      {
         /// buscamos la subcuenta equivalente en otro ejercicio
         $subc = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codsubcuenta = ".$this->var2str($cod).";");
         if($subc)
         {
            $old_sc = new subcuenta($subc[0]);
            
            /// buscamos la cuenta equivalente es ESTE ejercicio
            $cuenta = new cuenta();
            $new_c = $cuenta->get_by_codigo($old_sc->codcuenta, $ejercicio);
            if($new_c)
            {
               $new_sc = new subcuenta();
               $new_sc->codcuenta = $new_c->codcuenta;
               $new_sc->coddivisa = $old_sc->coddivisa;
               $new_sc->codejercicio = $ejercicio;
               $new_sc->codimpuesto = $old_sc->codimpuesto;
               $new_sc->codsubcuenta = $old_sc->codsubcuenta;
               $new_sc->descripcion = $old_sc->descripcion;
               $new_sc->idcuenta = $new_c->idcuenta;
               $new_sc->iva = $old_sc->iva;
			   $new_sc->alias = $old_alias->alias;
               $new_sc->recargo = $old_sc->recargo;
               if( $new_sc->save() )
               {
                  return $new_sc;
               }
               else
                  return FALSE;
            }
            else
            {
               $this->new_error_msg('No se ha encontrado la cuenta equivalente a '.$old_sc->codcuenta.' en el ejercicio '.$ejercicio.'.');
               return FALSE;
            }
         }
         else
         {
            $this->new_error_msg('No se ha encontrado ninguna subcuenta equivalente a '.$cod.' para copiar.');
            return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   /**
    * Devuelve la primera subcuenta del ejercicio $eje cuya cuenta madre
    * está marcada como cuenta especial $id.
    * @param type $id
    * @param type $eje
    * @return \subcuenta|boolean
    */
   public function get_cuentaesp($id, $eje)
   {
      $sql = "SELECT * FROM co_subcuentas WHERE idcuenta IN "
              ."(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = ".$this->var2str($id)
              ." AND codejercicio = ".$this->var2str($eje).") ORDER BY codsubcuenta ASC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         return new subcuenta($data[0]);
      }
      else
         return FALSE;
   }
   
      public function get_cuentaesp_subcuenta($idsubc,$id, $eje)
   {
      $sql = "SELECT * FROM co_subcuentas WHERE idsubcuenta = ".$this->var2str($idsubc)." and idcuenta IN "
              ."(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = ".$this->var2str($id)
              ." AND codejercicio = ".$this->var2str($eje).") ORDER BY codsubcuenta ASC;";
      
      $data = $this->db->select($sql);
      if($data)
      {
         return new subcuenta($data[0]);
      }
      else
         return FALSE;
   }
   
   public function tiene_saldo()
   {
      return !$this->floatcmp($this->debe, $this->haber, FS_NF0, TRUE);
   }
   
   public function exists()
   {
      if( is_null($this->idsubcuenta) )
      {
         return FALSE;
      }
      else
         return TRUE;
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      
      $limpiar_cache = FALSE;
      $totales = $this->get_totales();
      
      if( abs($this->debe - $totales['debe']) > .01 )
      {
         $this->debe = $totales['debe'];
         $limpiar_cache = TRUE;
      }
      
      if( abs($this->haber - $totales['haber']) > .01 )
      {
         $this->haber = $totales['haber'];
         $limpiar_cache = TRUE;
      }
      
      if( abs($this->saldo - $totales['saldo']) > .01 )
      {
         $this->saldo = $totales['saldo'];
         $limpiar_cache = TRUE;
      }
      
      if($limpiar_cache)
      {
         $this->clean_cache();
      }
      
      if( strlen($this->codsubcuenta)>0 AND strlen($this->descripcion)>0 )
      {
         return TRUE;
      }
      else
      {
         $this->new_error_msg('Faltan datos en la subcuenta.');
         return FALSE;
      }
   }
   
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codsubcuenta = ".$this->var2str($this->codsubcuenta)
                    .", idcuenta = ".$this->var2str($this->idcuenta)
                    .", codcuenta = ".$this->var2str($this->codcuenta)
                    .", codejercicio = ".$this->var2str($this->codejercicio)
                    .", coddivisa = ".$this->var2str($this->coddivisa)
                    .", codimpuesto = ".$this->var2str($this->codimpuesto)
                    .", descripcion = ".$this->var2str($this->descripcion)
                    .", recargo = ".$this->var2str($this->recargo)
                    .", iva = ".$this->var2str($this->iva)
                    .", debe = ".$this->var2str($this->debe)
                    .", haber = ".$this->var2str($this->haber)
                    .", saldo = ".$this->var2str($this->saldo)
					.", alias = ".$this->var2str($this->alias)
                    ."  WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codsubcuenta,idcuenta,codcuenta,
               codejercicio,coddivisa,codimpuesto,descripcion,debe,haber,saldo,recargo,alias,iva) VALUES
                      (".$this->var2str($this->codsubcuenta)
                    .",".$this->var2str($this->idcuenta)
                    .",".$this->var2str($this->codcuenta)
                    .",".$this->var2str($this->codejercicio)
                    .",".$this->var2str($this->coddivisa)
                    .",".$this->var2str($this->codimpuesto)
                    .",".$this->var2str($this->descripcion)
                    .",".$this->var2str($this->debe)
                    .",".$this->var2str($this->haber)
                    .",".$this->var2str($this->saldo)
                    .",".$this->var2str($this->recargo)
					.",".$this->var2str($this->alias)
                    .",".$this->var2str($this->iva).");";
            
            if( $this->db->exec($sql) )
            {
               $this->idsubcuenta = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
   
     public function modificar_sunas_debe_haber()
   {
    return $this->db->exec("UPDATE ".$this->table_name." SET debe = ".$this->var2str($this->debe).", haber = ".$this->var2str($this->haber)." WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).";");
   }
   
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idsubcuenta = ".$this->var2str($this->idsubcuenta).";");
   }
   
   public function clean_cache()
   {
      if( file_exists('tmp/'.FS_TMP_NAME.'libro_mayor/'.$this->idsubcuenta.'.pdf') )
      {
         if( !@unlink('tmp/'.FS_TMP_NAME.'libro_mayor/'.$this->idsubcuenta.'.pdf') )
         {
            $this->new_error_msg('Error al eliminar tmp/'.FS_TMP_NAME.'libro_mayor/'.$this->idsubcuenta.'.pdf');
         }
      }
      
      if( file_exists('tmp/'.FS_TMP_NAME.'libro_diario/'.$this->codejercicio.'.pdf') )
      {
         if( !@unlink('tmp/'.FS_TMP_NAME.'libro_diario/'.$this->codejercicio.'.pdf') )
         {
            $this->new_error_msg('Error al eliminar tmp/'.FS_TMP_NAME.'libro_diario/'.$this->codejercicio.'.pdf');
         }
      }
      
      if( file_exists('tmp/'.FS_TMP_NAME.'inventarios_balances/'.$this->codejercicio.'.pdf') )
      {
         if( !@unlink('tmp/'.FS_TMP_NAME.'inventarios_balances/'.$this->codejercicio.'.pdf') )
         {
            $this->new_error_msg('Error al eliminar tmp/'.FS_TMP_NAME.'inventarios_balances/'.$this->codejercicio.'.pdf');
         }
      }
   }
   
   public function all($num)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name." where idsubcuenta BETWEEN ".$this->var2str($num)." AND ".$this->var2str($num+302)." ;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
      public function subcoenta_compras($codejercicio)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcuenta IN  ( SELECT codcuenta FROM co_cuentas WHERE idcuentaesp = 'COMPRA' ) AND codejercicio = '".$codejercicio."'  ORDER BY descripcion ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
    
   
         public function subcoenta_compras_credito($codejercicio)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcuenta IN  ( SELECT codcuenta FROM co_cuentas WHERE idcuentaesp = 'DEVCOM' ) AND codejercicio = '".$codejercicio."'  ORDER BY descripcion ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   public function all_from_cuenta($idcuenta)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE idcuenta = ".$this->var2str($idcuenta)." ORDER BY codsubcuenta ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
      public function all_from_alias($alias)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE alias = ".$this->var2str($alias)." ORDER BY codsubcuenta ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   
   public function all_from_ejercicio($codejercicio)
   {
      $sublist = array();
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codejercicio = ".$this->var2str($codejercicio).
              " ORDER BY codsubcuenta ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   public function search($query)
   {
      $sublist = array();
      $query = strtolower( $this->no_html($query) );
      $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codsubcuenta LIKE '".$query."%' OR codsubcuenta LIKE '%".$query."'
               OR lower(descripcion) LIKE '%".$query."%'
               ORDER BY codejercicio DESC, codcuenta ASC;");
      if($subcuentas)
      {
         foreach($subcuentas as $s)
            $sublist[] = new subcuenta($s);
      }
      return $sublist;
   }
   
   public function search_by_ejercicio($ejercicio, $query)
   {
      $query = $this->escape_string( strtolower( trim($query) ) );
      
      $sublist = $this->cache->get_array('search_subcuenta_ejercicio_'.$ejercicio.'_'.$query);
      if( count($sublist) < 1 )
      {
         $subcuentas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codejercicio = ".$this->var2str($ejercicio).
              " AND (codsubcuenta LIKE '".$query."%' OR codsubcuenta LIKE '%".$query."'
               OR lower(descripcion) LIKE '%".$query."%' OR alias LIKE '%".$query."%' )
               ORDER BY codcuenta ASC;");
         
         if($subcuentas)
         {
            foreach($subcuentas as $s)
               $sublist[] = new subcuenta($s);
         }
         
         $this->cache->set('search_subcuenta_ejercicio_'.$ejercicio.'_'.$query, $sublist, 300);
      }
      
      return $sublist;
   }
}
