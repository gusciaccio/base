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

require_model('asiento.php');
require_model('anticipos_proveedor.php');

class contabilidad_asientos extends fs_controller
{
   public $asiento;
   public $resultados;
   public $offset;
   public $desde;
   public $hasta;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Asientos', 'contabilidad', FALSE, TRUE);
   }
   
   protected function private_core()
   {
      $this->asiento = new asiento();
      $anticipo = new anticipos_proveedor();
	   if(isset($_POST['desde']))
	   {
	    $this->desde = $_POST['desde'];
		if(!preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i',$this->desde) ) $this->desde ="99";
		}
	 if(isset($_GET['desde']))
	   {
	    $this->desde = $_GET['desde'];
		if(!preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i',$this->desde) ) $this->desde ="99";
		}
	  	if(isset($_POST['hasta']))
	   {
	    $this->hasta = $_POST['hasta'];
		if(!preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i',$this->hasta) ) $this->hasta ="99";
		} 
		if(isset($_GET['hasta']))
	   {
	    $this->hasta = $_GET['hasta'];
		if(!preg_match('/^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$/i',$this->hasta) ) $this->hasta ="99";
		}
	 
      if( isset($_GET['delete']) )
      {
         $asiento = $this->asiento->get($_GET['delete']);
         if($asiento)
         {
            if( $asiento->delete() )
            {
				$anticipo->modif_anticipo_idasiento($_GET['delete'],0);
               $this->new_message("Asiento eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el asiento!");
         }
         else
            $this->new_error_msg("¡Asiento no encontrado!");
      }
      else if( isset($_GET['renumerar']) )
      {
         if( $this->asiento->renumerar() )
         {
            $this->new_message("Asientos renumerados.");
         }
      }
      
      $this->offset = 0;
      if( isset($_GET['offset']) )
      {
         $this->offset = intval($_GET['offset']);
      }
      
      if( isset($_GET['descuadrados']) )
      {
         $this->resultados = $this->asiento->descuadrados();
      }
	  else if( isset($_GET['mayorizados']) )
      {
         $this->resultados = $this->asiento->all_mayorizados($this->offset);
      }	  
      else if($this->query or $this->desde or $this->hasta )
      {
         $this->resultados = $this->asiento->search($this->desde,$this->hasta,$this->query, $this->offset);
      }

      else
         $this->resultados = $this->asiento->all_sin_mayorizar($this->offset);
   }
   
  
   
   public function anterior_url()
   {
      $url = '';
	  
	  if( !isset($_GET['descuadrados']) && !isset($_GET['mayorizados'])) 
	  {
			  if($this->query != '' AND $this->offset > 0)
			  {
				 $url = $this->url()."&query=".$this->query."&desde=".$this->desde."&hasta=".$this->hasta."&offset=".($this->offset-FS_ITEM_LIMIT);
			  }
			  else if($this->query == '' AND $this->offset > 0)
			  {
				 $url = $this->url()."&desde=".$this->desde."&hasta=".$this->hasta."&offset=".($this->offset-FS_ITEM_LIMIT);
			  }
      }
      else if( isset($_GET['descuadrados']) )
	  {
	  if($_GET['descuadrados'] == TRUE ) 
	  		{
			  if($this->query != '' AND $this->offset > 0)
			  {
				 $url = $this->url()."&query=".$this->query."&mayorizados=true&offset=".($this->offset-FS_ITEM_LIMIT).'&descuadrados=TRUE';
			  }
			  else if($this->query == '' AND $this->offset > 0)
			  {
				 $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT).'&descuadrados=TRUE';
			  }
      		}
	  }			  
      else if( isset($_GET['mayorizados']) )
	  {
	  if($_GET['mayorizados'] == TRUE ) 
	  		{
			  if($this->query != '' AND $this->offset > 0)
			  {
				 $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT).'&mayorizados=TRUE';
			  }
			  else if($this->query == '' AND $this->offset > 0)
			  {
				 $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT).'&mayorizados=TRUE';
			  }
			 } 
      }
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
	 
	  if( !isset($_GET['descuadrados']) && !isset($_GET['mayorizados']))
	  {
	  $this->solapa='all';
			  if($this->query != '' AND count($this->resultados) == FS_ITEM_LIMIT)
			  {
				 $url = $this->url()."&query=".$this->query."&desde=".$this->desde."&hasta=".$this->hasta."&offset=".($this->offset+FS_ITEM_LIMIT);
			  }
			  else if($this->query == '' AND count($this->resultados) == FS_ITEM_LIMIT)
			  {
				 $url = $this->url()."&desde=".$this->desde."&hasta=".$this->hasta."&offset=".($this->offset+FS_ITEM_LIMIT);
			  }
      } 
	  else if( isset($_GET['descuadrados']) )
	  {
	  if($_GET['descuadrados'] == TRUE ) 
	  	  	{
			$this->solapa='des';
			  if($this->query != '' AND count($this->resultados) == FS_ITEM_LIMIT)
			  {
				 $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT).'&descuadrados=TRUE';
			  }
			  else if($this->query == '' AND count($this->resultados) == FS_ITEM_LIMIT)
			  {
				 $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).'&descuadrados=TRUE';
			  }
      		}
		}	
      else if( isset($_GET['mayorizados']) )
	  {
	  if($_GET['mayorizados'] == TRUE ) 
	  		{
			$this->solapa='may';
			  if($this->query != '' AND count($this->resultados) == FS_ITEM_LIMIT)
			  {
				 $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT).'&mayorizados=TRUE';
			  }
			  else if($this->query == '' AND count($this->resultados) == FS_ITEM_LIMIT)
			  {
				 $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).'&mayorizados=TRUE';
			  }
      		}
	   }			
      return $url;
   }
   
   public function total_asientos()
   {
      $data = $this->db->select("SELECT COUNT(idasiento) as total FROM co_asientos;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
   
    public function total_asientos_no_mayor()
   {
      $data = $this->db->select("SELECT COUNT(idasiento) as total FROM co_asientos WHERE mayorizado = '0' ;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
   
       public function total_asientos_mayor()
   {
      $data = $this->db->select("SELECT COUNT(idasiento) as total FROM co_asientos WHERE mayorizado = '1' ;");
      if($data)
      {
         return intval($data[0]['total']);
      }
      else
         return 0;
   }
   
          public function total_descuadrados()
   {
      $data = $this->asiento->descuadrados();
      if($data)
      {
         return count($data);
      }
      else
         return 0;
   }
   
}
