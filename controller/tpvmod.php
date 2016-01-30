<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2015-2016  Francisco Javier Trujillo Jimenez  javier.trujillo.jimenez@gmail.com
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

require_model('agente.php');
require_model('albaran_cliente.php');
require_model('pedido_cliente.php');
require_model('presupuesto_cliente.php');
require_model('articulo_proveedor_plus.php');
require_model('almacen.php');
require_model('articulo.php');
require_model('caja.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('grupo_clientes.php');
require_model('impuesto.php');
require_model('serie.php');
require_model('tarifa.php');
require_model('terminal_caja.php');

class tpvmod extends fs_controller
{
   public $agente;
   public $almacen;
   public $allow_delete;
   public $articulo;
   public $caja;
   public $cliente;
   public $cliente_s;
   public $divisa;
   public $ejercicio;
   public $equivalentes;
   public $familia;
   public $forma_pago;
   public $imprimir_descripciones;
   public $imprimir_observaciones;
   public $impuesto;
   public $results;
   public $serie;
   public $terminal;
   public $ultimas_compras;
   public $ultimas_ventas;
   public $linea_mejor=array();
   public $lista_proveedores=array();
   public $tipo="factura";
   public $vienede;
   public $id_documento;
   public $clientedefault="000001";
   public $documento;
   /*la opcion por defecto aqui la inicializo a facturas. Pero si esta activado
    * Los presupuestos la cambio a presupuestos en el controlador
    */


   public function __construct()
   {
      parent::__construct(__CLASS__, 'TPVMOD', 'TPV');
   }

   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);

      $this->articulo = new articulo();
      $this->cliente = new cliente();
      $this->cliente_s = $this->cliente->get($this->clientedefault);//cargo el cliente por defecto que en mi caso es cliente de contado con codcliente 000001 si no tienes cliente por defecto pon false
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->carga_proveedores();
      $this->results = array();

      if( isset($_REQUEST['buscar_cliente']) )
      {
         $this->buscar_cliente();
      }
      else if($this->query != '')
      {
         $this->new_search();
      }
      else if( isset($_REQUEST['referencia4precios']) )
      {
         $this->get_precios_articulo();
      }
      else
      {
         if( isset($_GET['edita']) )
         {
             if($_GET['id'])
             {
                 $this->id_documento=$_GET['id'];

                 if($_GET['edita']=="albaran")
                 {
                     $this->vienede="albaran";
                     $this->template="tpvmodalbaran";
                     $albaran=new albaran_cliente();
                     $this->documento = $albaran->get($_GET['id']);
                     if($this->documento)
                      {
                         $this->page->title = $this->documento->codigo;

                         /// cargamos el cliente
                         $this->cliente_s = $this->cliente->get($this->documento->codcliente);

                         /// comprobamos el presupuesto
                         $this->documento->full_test();
                      }
                      else
                         $this->new_error_msg("¡".FS_ALBARAN." de cliente no encontrado!");
                 }
                 elseif($_GET['edita']=="presupuesto")
                 {
                     $this->vienede="presupuesto";
                     $this->template="tpvmodpresupuesto";
                     $presupuesto=new presupuesto_cliente();
                     $this->documento = $presupuesto->get($_GET['id']);
                     if($this->documento)
                      {
                         $this->page->title = $this->documento->codigo;

                         /// cargamos el cliente
                         $this->cliente_s = $this->cliente->get($this->documento->codcliente);

                         /// comprobamos el albarán
                         $this->documento->full_test();
                      }
                      else
                         $this->new_error_msg("¡".FS_ALBARAN." de cliente no encontrado!");
                 }
                 elseif($_GET['edita']=="pedido")
                 {
                     $this->vienede="pedido";
                     $this->template="tpvmodpedido";
                     $pedido=new pedido_cliente();
                     $this->documento = $pedido->get($_GET['id']);
                     if($this->documento)
                      {
                         $this->page->title = $this->documento->codigo;

                         /// cargamos el cliente
                         $this->cliente_s = $this->cliente->get($this->documento->codcliente);

                         /// comprobamos el albarán
                         $this->documento->full_test();
                      }
                      else
                         $this->new_error_msg("¡".FS_PRESUPUESTO." de cliente no encontrado!");
                 }
                 elseif($_GET['edita']=="factura")
                 {
                     $this->vienede="factura";
                     $this->template="tpvmodfactura";
                     $factura=new factura_cliente();
                     $this->documento = $factura->get($_GET['id']);
                     if($this->documento)
                      {
                         $this->page->title = $this->documento->codigo;

                         /// cargamos el cliente
                         $this->cliente_s = $this->cliente->get($this->documento->codcliente);

                         /// comprobamos el albarán
                         $this->documento->full_test();
                      }
                      else
                         $this->new_error_msg("¡".FS_PRESUPUESTO." de cliente no encontrado!");
                 }
                 else
                 {
                     $this->new_error_msg("No se ha pasado ningun id en el factura");
                 }
             }
             else
             {
                $this->cliente_s = $this->cliente->get($this->clientedefault);
             }
         }
         $this->agente = $this->user->get_agente();
         $this->almacen = new almacen();
         $this->divisa = new divisa();
         $this->ejercicio = new ejercicio();
         $this->forma_pago = new forma_pago();
         $this->serie = new serie();

         $this->imprimir_descripciones = FALSE;
         if( isset($_REQUEST['imprimir_desc']) )
         {
            $this->imprimir_descripciones = TRUE;
         }

         $this->imprimir_observaciones = FALSE;
         if( isset($_REQUEST['imprimir_obs']) )
         {
            $this->imprimir_observaciones = TRUE;
         }

         if($this->agente)
         {
            $this->caja = FALSE;
            $this->terminal = FALSE;
            $caja = new caja();
            $terminal0 = new terminal_caja();
            foreach($caja->all_by_agente($this->agente->codagente) as $cj)
            {
               if( $cj->abierta() )
               {
                  $this->caja = $cj;
                  $this->terminal = $terminal0->get($cj->fs_id);
                  break;
               }
            }

            if(!$this->caja)
            {
               if( isset($_POST['terminal']) )
               {
                  $this->terminal = $terminal0->get($_POST['terminal']);
                  if(!$this->terminal)
                  {
                     $this->new_error_msg('Terminal no encontrado.');
                  }
                  else if( $this->terminal->disponible() )
                  {
                     $this->caja = new caja();
                     $this->caja->fs_id = $this->terminal->id;
                     $this->caja->codagente = $this->agente->codagente;
                     $this->caja->dinero_inicial = floatval($_POST['d_inicial']);
                     $this->caja->dinero_fin = floatval($_POST['d_inicial']);
                     if( $this->caja->save() )
                     {
                        $this->new_message("Caja iniciada con ".$this->show_precio($this->caja->dinero_inicial) );
                     }
                     else
                        $this->new_error_msg("¡Imposible guardar los datos de caja!");
                  }
                  else
                     $this->new_error_msg('El terminal ya no está disponible.');
               }
               else if( isset($_GET['terminal']) )
               {
                  $this->terminal = $terminal0->get($_GET['terminal']);
                  if($this->terminal)
                  {
                     $this->terminal->abrir_cajon();
                     $this->terminal->save();
                  }
                  else
                     $this->new_error_msg('Terminal no encontrado.');
               }
            }

            if($this->caja)
            {
               /*if( isset($_POST['cliente']) )
               {
                  $this->cliente_s = $this->cliente->get($_POST['cliente']);
               }
               else if($this->terminal)
               {
                  $this->cliente_s = $this->cliente->get($this->terminal->codcliente);
               }

               if(!$this->cliente_s)
               {
                  foreach($this->cliente->all() as $cli)
                  {
                     $this->cliente_s = $cli;
                     break;
                  }
               }*/

               if( isset($_GET['abrir_caja']) )
               {
                  $this->abrir_caja();
               }
               else if( isset($_GET['cerrar_caja']) )
               {
                  $this->cerrar_caja();
               }
               else if( isset($_POST['cliente']) )
               {
                  if( intval($_POST['numlineas']) > 0 )
                  {
                     if( isset($_POST['tipo']) )
			{
                            if($_POST['tipo'] == 'presupuesto')
                            {
                                $this->nuevo_presupuesto_cliente();
                            }
                            else if($_POST['tipo'] == 'pedido')
                            {
               			$this->nuevo_pedido_cliente();
                            }
                            else if($_POST['tipo'] == 'albaran')
                            {
                                $this->nuevo_albaran_cliente();
                            }
                            else if($_POST['tipo'] == 'factura')
                            {
                                $this->nueva_factura_cliente();
                            }
                            else if($_POST['tipo'] == 'guardar_albaran')
                            {
                                $this->edita_albaran_cliente();
                            }
                            else if($_POST['tipo'] == 'guardar_presupuesto')
                            {
                                $this->edita_presupuesto_cliente();
                            }
                            else if($_POST['tipo'] == 'guardar_pedido')
                            {
                                $this->edita_pedido_cliente();
                            }
                            else if($_POST['tipo'] == 'guardar_factura')
                            {
                                $this->edita_factura_cliente();
                            }
         		}
                  }
               }
               else if( isset($_GET['reticket']) )
               {
                  $this->reimprimir_ticket();
               }
               else if( isset($_GET['delete']) )
               {
                  $this->borrar_ticket();
               }
            }
            else
            {
               $this->results = $terminal0->disponibles();
            }
         }
         else
         {
            $this->new_error_msg('No tienes un <a href="'.$this->user->url().'">agente asociado</a>
               a tu usuario, y por tanto no puedes hacer tickets.');
         }
      }
   }

   private function carga_proveedores()
   {
       $proveedores = $this->db->select("SELECT * FROM proveedores ORDER BY nombre ASC;");
         if($proveedores)
         {
            foreach($proveedores as $p)
               $this->lista_proveedores[$p['codproveedor']] = $p['nombre'];
         }
   }

   private function buscar_cliente()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;

      $json = array();
      foreach($this->cliente->search($_REQUEST['buscar_cliente']) as $cli)
      {
         $json[] = array('value' => "$cli->nombre Tlf:$cli->telefono1  Tlf2:$cli->telefono2", 'data' => $cli->codcliente);
      }

      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json) );
   }

   public function get_pvp_iva($ref)
  {
      $articulo=new articulo();
       $articulo=$articulo->get($ref);
       if($articulo->get_iva()!==FALSE)
        if($articulo->get_iva()===0)
            return $articulo->pvp;
        elseif($articulo->get_iva()>0)
            return round($articulo->pvp+($articulo->pvp*$articulo->get_iva()/100), 3);
      else
        return false;
  }



  public function get_linea_mejor($ref,$pvp,$iva)
   {
       $mejor_proveedor=new articulo_proveedor_plus();
       $mejor_proveedor=$mejor_proveedor->getmejorarray($ref);
       $flag="Nada";
       if($mejor_proveedor['pvd'])
       {
           $flag="Entra";
            if($iva===0)
                $this->linea_mejor['pvp_iva']=$pvp;
            elseif($iva>0)
                $this->linea_mejor['pvp_iva']=round($pvp+($pvp*$iva/100),3);
            if($iva===0)
                $this->linea_mejor=$mejor_proveedor['pvd'];
            elseif($iva>0)
                $this->linea_mejor['pvd_iva']=round($mejor_proveedor['pvd']+($mejor_proveedor['pvd']*$iva/100), 3);
            $this->linea_mejor['proveedor']=$mejor_proveedor['codproveedor'];
            $this->linea_mejor['stock']=$mejor_proveedor['stock'];
            $this->linea_mejor['porcentaje']= round((($this->linea_mejor['pvp_iva']-$this->linea_mejor['pvd_iva'])/$this->linea_mejor['pvd_iva']*100), 2)."%";
       }
       else {
           $flag="No entra";
           $this->linea_mejor=NULL;
       }

       return $flag;
   }

   public function get_pvd_mejor($ref)
   {
       $mejor_proveedor=new articulo_proveedor_plus();
       $mejor_proveedor=$mejor_proveedor->getmejor($ref);
       if($mejor_proveedor)
            return  $mejor_proveedor->get_pvd_iva();
        else
            return false;
   }

   public function porcentaje_ganancia($ref)
   {
       return round((($this->get_pvp_iva($ref)-$this->get_pvd_mejor($ref))/$this->get_pvd_mejor($ref)*100), 2)."%";
   }

   public function get_stock_virtual($ref)
   {
       $mejor_proveedor=new articulo_proveedor_plus();
       $mejor_proveedor=$mejor_proveedor->getmejor($ref);
       if($mejor_proveedor)
            return  $mejor_proveedor->stock;
        else
            return false;
   }

   public function nom_proveedor($ref)
   {
       if($ref)
       {
           if($this->lista_proveedores[$ref])
            return  $this->lista_proveedores[$ref];
           else
            return false;
       }
        else
            return false;
   }


   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/tpv_recambios';
      //$this->template=false;

      $codfamilia = '';
      if( isset($_POST['codfamilia']) )
      {
         $codfamilia = $_POST['codfamilia'];
      }

      $con_stock = isset($_POST['con_stock']);
      $this->results = $this->articulo->search($this->query, 0, $codfamilia, $con_stock);

      /// añadimos el descuento y la cantidad
      foreach($this->results as $i => $value)
      {
         $this->results[$i]->dtopor = 0;
         $this->results[$i]->cantidad = 1;
      }

      /// ejecutamos las funciones de las extensiones
      foreach($this->extensions as $ext)
      {
         if($ext->type == 'function' AND $ext->params == 'new_search')
         {
            $name = $ext->text;
            $name($this->db, $this->results);
         }
      }

      $cliente = $this->cliente->get($_POST['codcliente']);
      if($cliente)
      {
         if($cliente->regimeniva == 'Exento')
         {
            foreach($this->results as $i => $value)
               $this->results[$i]->iva = 0;
         }

         if($cliente->codgrupo)
         {
            $grupo0 = new grupo_clientes();
            $tarifa0 = new tarifa();

            $grupo = $grupo0->get($cliente->codgrupo);
            if($grupo)
            {
               $tarifa = $tarifa0->get($grupo->codtarifa);
               if($tarifa)
               {
                  $tarifa->set_precios($this->results);
               }
            }
         }
      }
      //header('Content-Type: application/json');
      //echo json_encode($this->results);
   }



   private function get_precios_articulo()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/tpv_cambios_precios';
      $this->articulo = $this->articulo->get($_REQUEST['referencia4precios']);
   }

   public function get_tarifas_articulo($ref)
   {
      $tarlist = array();
      $articulo = new articulo();
      $tarifa = new tarifa();

      foreach($tarifa->all() as $tar)
      {
         $art = $articulo->get($ref);
         if($art)
         {
            $art->dtopor = 0;
            $aux = array($art);
            $tar->set_precios($aux);
            $tarlist[] = $aux[0];
         }
      }

      return $tarlist;
   }




   private function nuevo_presupuesto_cliente()
   {
      $continuar = TRUE;

      $cliente = $this->cliente->get($_POST['cliente']);
      if( !$cliente )
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }

      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }

      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( !$ejercicio )
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
      if( !$divisa )
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }

      $presupuesto = new presupuesto_cliente();

      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$presupuesto->url().'">Presupuestos</a>
               para ver si el presupuesto se ha guardado correctamente.');
         $continuar = FALSE;
      }

      if($continuar)
      {
         $presupuesto->fecha = $this->today();
         $presupuesto->finoferta = date("Y-m-d", strtotime($_POST['fecha']." +30 days"));
         $presupuesto->codalmacen = $almacen->codalmacen;
         $presupuesto->codejercicio = $ejercicio->codejercicio;
         $presupuesto->codserie = $serie->codserie;
         $presupuesto->codpago = $forma_pago->codpago;
         $presupuesto->coddivisa = $divisa->coddivisa;
         $presupuesto->tasaconv = $divisa->tasaconv;
         $presupuesto->codagente = $this->agente->codagente;
         $presupuesto->observaciones = $_POST['observaciones'];
         $presupuesto->numero2 = $_POST['numero2'];
         $presupuesto->irpf = $serie->irpf;
         $presupuesto->porcomision = $this->agente->porcomision;

         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $presupuesto->codcliente = $cliente->codcliente;
               $presupuesto->cifnif = $cliente->cifnif;
               $presupuesto->nombrecliente = $cliente->razonsocial;
               $presupuesto->apartado = $d->apartado;
               $presupuesto->ciudad = $d->ciudad;
               $presupuesto->coddir = $d->id;
               $presupuesto->codpais = $d->codpais;
               $presupuesto->codpostal = $d->codpostal;
               $presupuesto->direccion = $d->direccion;
               $presupuesto->provincia = $d->provincia;
               break;
            }
         }

         if( is_null($presupuesto->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $presupuesto->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 0; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                   $linea = new linea_presupuesto_cliente();
                     $linea->idpresupuesto = $presupuesto->idpresupuesto;
                     $linea->descripcion = $_POST['desc_'.$i];
                     $articulo = $art0->get($_POST['referencia_'.$i]);
                     if($articulo)
                     {
                        $linea->referencia = $articulo->referencia;
                     }
                     if( !$serie->siniva AND $cliente->regimeniva != 'Exento' )
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

                     if($linea->iva > 0)
                        $linea->irpf = $presupuesto->irpf;

                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     //$linea->dtopor = floatval($_POST['000001'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['neto_'.$i]);

                     if( $linea->save() )
                     {
                        $presupuesto->neto += $linea->pvptotal;
                        $presupuesto->totaliva += ($linea->pvptotal * $linea->iva/100);
                        $presupuesto->totalirpf += ($linea->pvptotal * $linea->irpf/100);
                        $presupuesto->totalrecargo += ($linea->pvptotal * $linea->recargo/100);
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
               $presupuesto->neto = round($presupuesto->neto, FS_NF0);
               $presupuesto->totaliva = round($presupuesto->totaliva, FS_NF0);
               $presupuesto->totalirpf = round($presupuesto->totalirpf, FS_NF0);
               $presupuesto->totalrecargo = round($presupuesto->totalrecargo, FS_NF0);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva - $presupuesto->totalirpf + $presupuesto->totalrecargo;

					if( $presupuesto->save() )
               {
                  $this->new_message("<a href='./index.php?page=tpvmod&edita=presupuesto&id=".$presupuesto->idpresupuesto."'>".ucfirst(FS_PRESUPUESTO)."</a> guardado correctamente. <a href='index.php?page=imprimir_presu_pedi&presupuesto=TRUE&id=".$presupuesto->idpresupuesto."'>Imprimir</a>");
                  $this->new_change(ucfirst(FS_PRESUPUESTO).' a Cliente '.$presupuesto->codigo, $presupuesto->url(), TRUE);
				  $this->cliente_s = $this->cliente->get($this->clientedefault);//reseteo el cliente
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>".FS_PRESUPUESTO."</a>!");
            }
            else if( $presupuesto->delete() )
            {
               $this->new_message(ucfirst(FS_PRESUPUESTO)." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$presupuesto->url()."'>".FS_PRESUPUESTO."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_PRESUPUESTO."!");
      }
   }

   private function nueva_factura_cliente()
   {
      $continuar = TRUE;
      $cliente = $this->cliente->get($_POST['cliente']);
      if( !$cliente )
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }

      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }

      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( !$ejercicio )
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
      if( ! $divisa )
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }

      $factura = new factura_cliente();



      if($continuar)
      {
         $factura->fecha = $this->today();
         $factura->codalmacen = $almacen->codalmacen;
         $factura->codejercicio = $ejercicio->codejercicio;
         $factura->codserie = $serie->codserie;
         $factura->codpago = $forma_pago->codpago;
         $factura->coddivisa = $divisa->coddivisa;
         $factura->tasaconv = $divisa->tasaconv;
         $factura->codagente = $this->agente->codagente;
         $factura->observaciones = $_POST['observaciones'];
         $factura->numero2 = $_POST['numero2'];
         $factura->irpf = $serie->irpf;
         $factura->porcomision = $this->agente->porcomision;

         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $factura->codcliente = $cliente->codcliente;
               $factura->cifnif = $cliente->cifnif;
               $factura->nombrecliente = $cliente->razonsocial;
               $factura->apartado = $d->apartado;
               $factura->ciudad = $d->ciudad;
               $factura->coddir = $d->id;
               $factura->codpais = $d->codpais;
               $factura->codpostal = $d->codpostal;
               $factura->direccion = $d->direccion;
               $factura->provincia = $d->provincia;
               break;
            }
         }

         if( is_null($factura->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $factura->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
                     $linea = new linea_factura_cliente();
                     $linea->idfactura = $factura->idfactura;
                     if( isset($_POST['referencia_'.$i]) )
                     {
                        $articulo = $art0->get($_POST['referencia_'.$i]);
                        if($articulo)
                        {
                            $linea->referencia = $articulo->referencia;
                        }
                     }
                     $linea->descripcion = $_POST['desc_'.$i];

                     if( !$serie->siniva AND $cliente->regimeniva != 'Exento' )
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

                     if($linea->iva > 0)
                        $linea->irpf = $factura->irpf;

                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     //$linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['neto_'.$i]);

                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        if($articulo)
                            $articulo->sum_stock($factura->codalmacen, 0 - $linea->cantidad);

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

            if($continuar)
            {
               /// redondeamos
               $factura->neto = round($factura->neto, FS_NF0);
               $factura->totaliva = round($factura->totaliva, FS_NF0);
               $factura->totalirpf = round($factura->totalirpf, FS_NF0);
               $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
               $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;

	       if( $factura->save() )
               {
                  $factura->get_lineas_iva();
                  $this->new_message("<a href='./index.php?page=tpvmod&edita=factura&id=".$factura->idfactura."'>Factura</a> guardada correctamente. <a  href='index.php?page=factura_detallada&id=".$factura->idfactura."'>Imprimir</a>");
                  $this->new_change('Factura Cliente '.$factura->codigo, $factura->url(), TRUE);
		  $this->cliente_s = $this->cliente->get($this->clientedefault);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar la <a href='".$factura->url()."'>Factura</a>!");
            }
            else if( $factura->delete() )
            {
               $this->new_message("Factura eliminada correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar la <a href='".$factura->url()."'>Factura</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar la Factura!");
      }
   }

   private function nuevo_pedido_cliente()
   {
      $continuar = TRUE;

      $cliente = $this->cliente->get($_POST['cliente']);
      if( !$cliente )
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }

      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }

      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( !$ejercicio )
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
      if( !$divisa )
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }

      $pedido = new pedido_cliente();

      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$pedido->url().'">Pedidos</a>
               para ver si el pedido se ha guardado correctamente.');
         $continuar = FALSE;
      }

      if($continuar)
      {
         
         $pedido->fecha = $this->today();
         $pedido->codalmacen = $almacen->codalmacen;
         $pedido->codejercicio = $ejercicio->codejercicio;
         $pedido->codserie = $serie->codserie;
         $pedido->codpago = $forma_pago->codpago;
         $pedido->coddivisa = $divisa->coddivisa;
         $pedido->tasaconv = $divisa->tasaconv;
         $pedido->codagente = $this->agente->codagente;
         $pedido->observaciones = $_POST['observaciones'];
         $pedido->numero2 = $_POST['numero2'];
         $pedido->irpf = $serie->irpf;
         $pedido->porcomision = $this->agente->porcomision;

         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $pedido->codcliente = $cliente->codcliente;
               $pedido->cifnif = $cliente->cifnif;
               $pedido->nombrecliente = $cliente->razonsocial;
               $pedido->apartado = $d->apartado;
               $pedido->ciudad = $d->ciudad;
               $pedido->coddir = $d->id;
               $pedido->codpais = $d->codpais;
               $pedido->codpostal = $d->codpostal;
               $pedido->direccion = $d->direccion;
               $pedido->provincia = $d->provincia;
               break;
            }
         }

         if( is_null($pedido->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $pedido->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {

                 $linea = new linea_pedido_cliente();
                 $linea->idpedido = $pedido->idpedido;
                 if( isset($_POST['referencia_'.$i]) )
                 {
                    $articulo = $art0->get($_POST['referencia_'.$i]);
                    if($articulo)
                    {
                        $linea->referencia = $articulo->referencia;
                    }
                 }
                 $linea->descripcion = $_POST['desc_'.$i];

                 if( !$serie->siniva AND $cliente->regimeniva != 'Exento' )
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

                 if($linea->iva > 0)
                    $linea->irpf = $pedido->irpf;
                 //corregido de lo pasado en ventas
                 $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                 $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                 //$linea->dtopor = floatval($_POST['dto_'.$i]);
                 $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                 $linea->pvptotal = floatval($_POST['neto_'.$i]);

                 if( $linea->save() )
                 {
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

            if($continuar)
            {
               /// redondeamos
               $pedido->neto = round($pedido->neto, FS_NF0);
               $pedido->totaliva = round($pedido->totaliva, FS_NF0);
               $pedido->totalirpf = round($pedido->totalirpf, FS_NF0);
               $pedido->totalrecargo = round($pedido->totalrecargo, FS_NF0);
               $pedido->total = $pedido->neto + $pedido->totaliva - $pedido->totalirpf + $pedido->totalrecargo;

                if( $pedido->save() )
               {
                  $this->new_message("<a href='./index.php?page=tpvmod&edita=pedido&id=".$pedido->idpedido."'>".ucfirst(FS_PEDIDO)."</a> guardado correctamente. <a href='index.php?page=imprimir_presu_pedi&pedido=TRUE&id=".$pedido->idpedido."'>Imprimir</a>");
                  $this->new_change(ucfirst(FS_PEDIDO)." a Cliente ".$pedido->codigo, $pedido->url(), TRUE);
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

   private function nuevo_albaran_cliente()
   {
      $continuar = TRUE;
      $cliente = $this->cliente->get($_POST['cliente']);
      if( !$cliente )
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }

      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( !$ejercicio )
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
      if( !$divisa )
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }

      if( isset($_POST['imprimir_desc']) )
      {
         $this->imprimir_descripciones = TRUE;
         setcookie('imprimir_desc', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_descripciones = FALSE;
         setcookie('imprimir_desc', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      if( isset($_POST['imprimir_obs']) )
      {
         $this->imprimir_observaciones = TRUE;
         setcookie('imprimir_obs', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_observaciones = FALSE;
         setcookie('imprimir_obs', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      $albaran = new albaran_cliente();

      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }

      if( $continuar )
      {
         $albaran->fecha = $this->today();
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv;
         $albaran->codagente = $this->agente->codagente;
         $albaran->observaciones = $_POST['observaciones'];
         $albaran->numero2 = $_POST['numero2'];
         $albaran->irpf = $serie->irpf;
         $albaran->porcomision = $this->agente->porcomision;

         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $albaran->codcliente = $cliente->codcliente;
               $albaran->cifnif = $cliente->cifnif;
               $albaran->nombrecliente = $cliente->razonsocial;
               $albaran->apartado = $d->apartado;
               $albaran->ciudad = $d->ciudad;
               $albaran->coddir = $d->id;
               $albaran->codpais = $d->codpais;
               $albaran->codpostal = $d->codpostal;
               $albaran->direccion = $d->direccion;
               $albaran->provincia = $d->provincia;
               break;
            }
         }

         if( is_null($albaran->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $albaran->save() )
         {
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {

                 $linea = new linea_albaran_cliente();
                 $linea->idalbaran = $albaran->idalbaran;
                 $articulo = $this->articulo->get($_POST['referencia_'.$i]);
                 if($articulo)
                 {
                    $linea->referencia = $articulo->referencia;
                 }
                 $linea->descripcion = $_POST['desc_'.$i];

                 if( !$serie->siniva AND $cliente->regimeniva != 'Exento' )
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
                 //$linea->dtopor = floatval($_POST['dto_'.$i]);
                 $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                 $linea->pvptotal = floatval($_POST['neto_'.$i]);

                 if( $linea->save() )
                 {
                    /// descontamos del stock
                    if($articulo)
                        $articulo->sum_stock($albaran->codalmacen, 0 - $linea->cantidad);

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

               if( abs(floatval($_POST['tpv_total2']) - $albaran->total) >= .02 )
               {
                  $this->new_error_msg("El total difiere entre la vista y el controlador (".$_POST['tpv_total2'].
                          " frente a ".$albaran->total."). Debes informar del error.");
                  $albaran->delete();
               }
               else if( $albaran->save() )
               {
                  $this->new_message("<a href='./index.php?page=tpvmod&edita=albaran&id=".$albaran->idalbaran."'>".FS_ALBARAN."</a> guardado correctamente. <a  href='index.php?page=ventas_imprimir&albaran=TRUE&id=".$albaran->idalbaran."'>Imprimir</a>");
	          $this->cliente_s = $this->cliente->get($this->clientedefault);


                  /// actualizamos la caja
                  $this->caja->dinero_fin += $albaran->total;
                  $this->caja->tickets += 1;
                  $this->caja->ip = $_SERVER['REMOTE_ADDR'];
                  if( !$this->caja->save() )
                  {
                     $this->new_error_msg("¡Imposible actualizar la caja!");
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

      private function edita_albaran_cliente()
   {
      $continuar = TRUE;
      $this->cliente_s = $this->cliente->get($_POST['cliente']);
      if( !$this->cliente_s)
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }

      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( !$ejercicio )
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
      if( !$divisa )
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }

      if( isset($_POST['imprimir_desc']) )
      {
         $this->imprimir_descripciones = TRUE;
         setcookie('imprimir_desc', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_descripciones = FALSE;
         setcookie('imprimir_desc', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      if( isset($_POST['imprimir_obs']) )
      {
         $this->imprimir_observaciones = TRUE;
         setcookie('imprimir_obs', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_observaciones = FALSE;
         setcookie('imprimir_obs', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      $albaran = new albaran_cliente();
      $albaran = $albaran->get($_POST['id']);


      if( $continuar )
      {
         $albaran->fecha = $_POST['fecha'];
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv;
         $albaran->codagente = $this->agente->codagente;
         $albaran->observaciones = $_POST['observaciones'];
         $albaran->numero2 = $_POST['numero2'];
         $albaran->irpf = $serie->irpf;
         $albaran->porcomision = $this->agente->porcomision;

         foreach($this->cliente_s->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $albaran->codcliente = $this->cliente_s->codcliente;
               $albaran->cifnif = $this->cliente_s->cifnif;
               $albaran->nombrecliente = $this->cliente_s->razonsocial;
               $albaran->apartado = $d->apartado;
               $albaran->ciudad = $d->ciudad;
               $albaran->coddir = $d->id;
               $albaran->codpais = $d->codpais;
               $albaran->codpostal = $d->codpostal;
               $albaran->direccion = $d->direccion;
               $albaran->provincia = $d->provincia;
               break;
            }
         }
            if( isset($_POST['numlineas']) )
             {
                $numlineas = intval($_POST['numlineas']);

                $albaran->neto = 0;
                $albaran->totaliva = 0;
                $albaran->totalirpf = 0;
                $albaran->totalrecargo = 0;
                $lineas = $albaran->get_lineas();
                $articulo = new articulo();

                /// eliminamos las líneas que no encontremos en el $_POST
                foreach($lineas as $l)
                {
                   $encontrada = FALSE;
                   for($num = 0; $num <= $numlineas; $num++)
                   {
                      if( isset($_POST['idlinea_'.$num]) )
                      {
                         if($l->idlinea == intval($_POST['idlinea_'.$num]))
                         {
                            $encontrada = TRUE;
                            break;
                         }
                      }
                   }
                   if( !$encontrada )
                   {
                      if( $l->delete() )
                      {
                         /// actualizamos el stock
                         $art0 = $articulo->get($l->referencia);
                         if($art0)
                            $art0->sum_stock($albaran->codalmacen, $l->cantidad);
                      }
                      else
                         $this->new_error_msg("¡Imposible eliminar la línea del artículo ".$l->referencia."!");
                   }
                }

                /// modificamos y/o añadimos las demás líneas
                for($num = 0; $num <= $numlineas; $num++)
                {
                   $encontrada = FALSE;
                   if( isset($_POST['idlinea_'.$num]) )
                   {
                      foreach($lineas as $k => $value)
                      {
                         /// modificamos la línea
                         if($value->idlinea == intval($_POST['idlinea_'.$num]))
                         {

                            $encontrada = TRUE;
                            $cantidad_old = $value->cantidad;
                            $lineas[$k]->cantidad = floatval($_POST['cantidad_'.$num]);
                            $lineas[$k]->pvpunitario = floatval($_POST['pvp_'.$num]);
                            $lineas[$k]->dtopor = floatval($_POST['dto_'.$num]);
                            $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                            $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor)/100);
                            $lineas[$k]->descripcion = $_POST['desc_'.$num];

                            $lineas[$k]->codimpuesto = NULL;
                            $lineas[$k]->iva = 0;
                            $lineas[$k]->recargo = 0;
                            $lineas[$k]->irpf = floatval($_POST['irpf_'.$num]);
                            if( !$serie->siniva AND $this->cliente_s->regimeniva != 'Exento' )
                            {
                               $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                               if($imp0)
                                  $lineas[$k]->codimpuesto = $imp0->codimpuesto;

                               $lineas[$k]->iva = floatval($_POST['iva_'.$num]);
                               $lineas[$k]->recargo = floatval($_POST['recargo_'.$num]);
                            }

                            if( $lineas[$k]->save() )
                            {
                               $albaran->neto += $value->pvptotal;
                               $albaran->totaliva += $value->pvptotal * $value->iva/100;
                               $albaran->totalirpf += $value->pvptotal * $value->irpf/100;
                               $albaran->totalrecargo += $value->pvptotal * $value->recargo/100;

                               if($lineas[$k]->cantidad != $cantidad_old)
                               {
                                  /// actualizamos el stock
                                  $art0 = $articulo->get($value->referencia);
                                  if($art0)
                                     $art0->sum_stock($albaran->codalmacen, $cantidad_old - $lineas[$k]->cantidad);
                               }
                            }
                            else
                               $this->new_error_msg("¡Imposible modificar la línea del artículo ".$value->referencia."!");
                            break;
                         }
                      }

                      /// añadimos la línea
                      if(!$encontrada AND intval($_POST['idlinea_'.$num]) == -1 AND isset($_POST['referencia_'.$num]))
                      {
                         $linea = new linea_albaran_cliente();
                         $linea->idalbaran = $albaran->idalbaran;
                         $linea->descripcion = $_POST['desc_'.$num];

                         if( !$serie->siniva AND $this->cliente_s->regimeniva != 'Exento' )
                         {
                            $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                            if($imp0)
                               $linea->codimpuesto = $imp0->codimpuesto;

                            $linea->iva = floatval($_POST['iva_'.$num]);
                            $linea->recargo = floatval($_POST['recargo_'.$num]);
                         }

                         $linea->irpf = floatval($_POST['irpf_'.$num]);
                         $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                         $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                         $linea->dtopor = floatval($_POST['dto_'.$num]);
                         $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                         $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);

                         $art0 = $articulo->get( $_POST['referencia_'.$num] );
                         if($art0)
                         {
                            $linea->referencia = $art0->referencia;
                         }

                         if( $linea->save() )
                         {
                            if($art0)
                            {
                               /// actualizamos el stock
                               $art0->sum_stock($albaran->codalmacen, 0 - $linea->cantidad);
                            }

                            $albaran->neto += $linea->pvptotal;
                            $albaran->totaliva += $linea->pvptotal * $linea->iva/100;
                            $albaran->totalirpf += $linea->pvptotal * $linea->irpf/100;
                            $albaran->totalrecargo += $linea->pvptotal * $linea->recargo/100;
                         }
                         else
                            $this->new_error_msg("¡Imposible guardar la línea del artículo ".$linea->referencia."!");
                      }
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

               if( $albaran->save() )
               {
                  $this->new_message("<a href='./index.php?page=tpvmod&edita=albaran&id=".$albaran->idalbaran."'>".FS_ALBARAN."</a> guardado correctamente. <a  href='index.php?page=ventas_imprimir&albaran=TRUE&id=".$albaran->idalbaran."'>Imprimir</a>");



                  /// actualizamos la caja
                  $this->caja->dinero_fin += $albaran->total;
                  $this->caja->tickets += 1;
                  $this->caja->ip = $_SERVER['REMOTE_ADDR'];
                  if( !$this->caja->save() )
                  {
                     $this->new_error_msg("¡Imposible actualizar la caja!");
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
      $this->cliente_s = $this->cliente->get($this->clientedefault);
   }

   private function edita_pedido_cliente()
   {
      $continuar = TRUE;
      $this->cliente_s = $this->cliente->get($_POST['cliente']);
      if( !$this->cliente_s)
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }

      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( !$ejercicio )
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
      if( !$divisa )
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }

      if( isset($_POST['imprimir_desc']) )
      {
         $this->imprimir_descripciones = TRUE;
         setcookie('imprimir_desc', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_descripciones = FALSE;
         setcookie('imprimir_desc', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      if( isset($_POST['imprimir_obs']) )
      {
         $this->imprimir_observaciones = TRUE;
         setcookie('imprimir_obs', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_observaciones = FALSE;
         setcookie('imprimir_obs', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      $pedido = new pedido_cliente();
      $pedido = $pedido->get($_POST['id']);


      if( $continuar )
      {
         $pedido->fecha = $_POST['fecha'];
         $pedido->codalmacen = $almacen->codalmacen;
         $pedido->codejercicio = $ejercicio->codejercicio;
         $pedido->codserie = $serie->codserie;
         $pedido->codpago = $forma_pago->codpago;
         $pedido->coddivisa = $divisa->coddivisa;
         $pedido->tasaconv = $divisa->tasaconv;
         $pedido->codagente = $this->agente->codagente;
         $pedido->observaciones = $_POST['observaciones'];
         $pedido->numero2 = $_POST['numero2'];
         $pedido->irpf = $serie->irpf;
         $pedido->porcomision = $this->agente->porcomision;

         foreach($this->cliente_s->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $pedido->codcliente = $this->cliente_s->codcliente;
               $pedido->cifnif = $this->cliente_s->cifnif;
               $pedido->nombrecliente = $this->cliente_s->razonsocial;
               $pedido->apartado = $d->apartado;
               $pedido->ciudad = $d->ciudad;
               $pedido->coddir = $d->id;
               $pedido->codpais = $d->codpais;
               $pedido->codpostal = $d->codpostal;
               $pedido->direccion = $d->direccion;
               $pedido->provincia = $d->provincia;
               break;
            }
         }
            if( isset($_POST['numlineas']) )
             {
                $numlineas = intval($_POST['numlineas']);

                $pedido->neto = 0;
                $pedido->totaliva = 0;
                $pedido->totalirpf = 0;
                $pedido->totalrecargo = 0;
                $lineas = $pedido->get_lineas();
                $articulo = new articulo();

                /// eliminamos las líneas que no encontremos en el $_POST
                foreach($lineas as $l)
                {
                   $encontrada = FALSE;
                   for($num = 0; $num <= $numlineas; $num++)
                   {
                      if( isset($_POST['idlinea_'.$num]) )
                      {
                         if($l->idlinea == intval($_POST['idlinea_'.$num]))
                         {
                            $encontrada = TRUE;
                            break;
                         }
                      }
                   }
                   if( !$encontrada )
                   {
                      if( !$l->delete() )
                         $this->new_error_msg("¡Imposible eliminar la línea del artículo ".$l->referencia."!");
                   }
                }

                /// modificamos y/o añadimos las demás líneas
                for($num = 0; $num <= $numlineas; $num++)
                {
                   $encontrada = FALSE;
                   if( isset($_POST['idlinea_'.$num]) )
                   {
                      foreach($lineas as $k => $value)
                      {
                         /// modificamos la línea
                         if($value->idlinea == intval($_POST['idlinea_'.$num]))
                         {

                            $encontrada = TRUE;
                            $cantidad_old = $value->cantidad;
                            $lineas[$k]->cantidad = floatval($_POST['cantidad_'.$num]);
                            $lineas[$k]->pvpunitario = floatval($_POST['pvp_'.$num]);
                            $lineas[$k]->dtopor = floatval($_POST['dto_'.$num]);
                            $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                            $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor)/100);
                            $lineas[$k]->descripcion = $_POST['desc_'.$num];

                            $lineas[$k]->codimpuesto = NULL;
                            $lineas[$k]->iva = 0;
                            $lineas[$k]->recargo = 0;
                            $lineas[$k]->irpf = floatval($_POST['irpf_'.$num]);
                            if( !$serie->siniva AND $this->cliente_s->regimeniva != 'Exento' )
                            {
                               $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                               if($imp0)
                                  $lineas[$k]->codimpuesto = $imp0->codimpuesto;

                               $lineas[$k]->iva = floatval($_POST['iva_'.$num]);
                               $lineas[$k]->recargo = floatval($_POST['recargo_'.$num]);
                            }

                            if( $lineas[$k]->save() )
                            {
                               $pedido->neto += $value->pvptotal;
                               $pedido->totaliva += $value->pvptotal * $value->iva/100;
                               $pedido->totalirpf += $value->pvptotal * $value->irpf/100;
                               $pedido->totalrecargo += $value->pvptotal * $value->recargo/100;



                            }
                            else
                               $this->new_error_msg("¡Imposible modificar la línea del artículo ".$value->referencia."!");
                            break;
                         }
                      }

                      /// añadimos la línea
                      if(!$encontrada AND intval($_POST['idlinea_'.$num]) == -1 AND isset($_POST['referencia_'.$num]))
                      {
                         $linea = new linea_pedido_cliente();
                         $linea->idpedido = $pedido->idpedido;
                         $linea->descripcion = $_POST['desc_'.$num];

                         if( !$serie->siniva AND $this->cliente_s->regimeniva != 'Exento' )
                         {
                            $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                            if($imp0)
                               $linea->codimpuesto = $imp0->codimpuesto;

                            $linea->iva = floatval($_POST['iva_'.$num]);
                            $linea->recargo = floatval($_POST['recargo_'.$num]);
                         }

                         $linea->irpf = floatval($_POST['irpf_'.$num]);
                         $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                         $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                         $linea->dtopor = floatval($_POST['dto_'.$num]);
                         $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                         $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);

                         $art0 = $articulo->get( $_POST['referencia_'.$num] );
                         if($art0)
                         {
                            $linea->referencia = $art0->referencia;
                         }

                         if( $linea->save() )
                         {


                            $pedido->neto += $linea->pvptotal;
                            $pedido->totaliva += $linea->pvptotal * $linea->iva/100;
                            $pedido->totalirpf += $linea->pvptotal * $linea->irpf/100;
                            $pedido->totalrecargo += $linea->pvptotal * $linea->recargo/100;
                         }
                         else
                            $this->new_error_msg("¡Imposible guardar la línea del artículo ".$linea->referencia."!");
                      }
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

               if( $pedido->save() )
               {
                  $this->new_message("<a href='./index.php?page=tpvmod&edita=pedido&id=".$pedido->idpedido."'>".FS_ALBARAN."</a> guardado correctamente. <a  href='index.php?page=ventas_imprimir&pedido=TRUE&id=".$pedido->idpedido."'>Imprimir</a>");



                  /// actualizamos la caja
                  $this->caja->dinero_fin += $pedido->total;
                  $this->caja->tickets += 1;
                  $this->caja->ip = $_SERVER['REMOTE_ADDR'];
                  if( !$this->caja->save() )
                  {
                     $this->new_error_msg("¡Imposible actualizar la caja!");
                  }
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$pedido->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $pedido->delete() )
            {
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$pedido->url()."'>".FS_ALBARAN."</a>!");
      }
      $this->cliente_s = $this->cliente->get($this->clientedefault);
   }


   private function edita_presupuesto_cliente()
   {
      $continuar = TRUE;
      $this->cliente_s = $this->cliente->get($_POST['cliente']);
      if( !$this->cliente_s)
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }

      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( !$ejercicio )
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
      if( !$divisa )
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }

      if( isset($_POST['imprimir_desc']) )
      {
         $this->imprimir_descripciones = TRUE;
         setcookie('imprimir_desc', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_descripciones = FALSE;
         setcookie('imprimir_desc', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      if( isset($_POST['imprimir_obs']) )
      {
         $this->imprimir_observaciones = TRUE;
         setcookie('imprimir_obs', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_observaciones = FALSE;
         setcookie('imprimir_obs', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      $presupuesto = new presupuesto_cliente();
      $presupuesto = $presupuesto->get($_POST['id']);


      if( $continuar )
      {
         $presupuesto->fecha = $_POST['fecha'];
         $presupuesto->codalmacen = $almacen->codalmacen;
         $presupuesto->codejercicio = $ejercicio->codejercicio;
         $presupuesto->codserie = $serie->codserie;
         $presupuesto->codpago = $forma_pago->codpago;
         $presupuesto->coddivisa = $divisa->coddivisa;
         $presupuesto->tasaconv = $divisa->tasaconv;
         $presupuesto->codagente = $this->agente->codagente;
         $presupuesto->observaciones = $_POST['observaciones'];
         $presupuesto->numero2 = $_POST['numero2'];
         $presupuesto->irpf = $serie->irpf;
         $presupuesto->porcomision = $this->agente->porcomision;

         foreach($this->cliente_s->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $presupuesto->codcliente = $this->cliente_s->codcliente;
               $presupuesto->cifnif = $this->cliente_s->cifnif;
               $presupuesto->nombrecliente = $this->cliente_s->razonsocial;
               $presupuesto->apartado = $d->apartado;
               $presupuesto->ciudad = $d->ciudad;
               $presupuesto->coddir = $d->id;
               $presupuesto->codpais = $d->codpais;
               $presupuesto->codpostal = $d->codpostal;
               $presupuesto->direccion = $d->direccion;
               $presupuesto->provincia = $d->provincia;
               break;
            }
         }
            if( isset($_POST['numlineas']) )
             {
                $numlineas = intval($_POST['numlineas']);

                $presupuesto->neto = 0;
                $presupuesto->totaliva = 0;
                $presupuesto->totalirpf = 0;
                $presupuesto->totalrecargo = 0;
                $lineas = $presupuesto->get_lineas();
                $articulo = new articulo();

                /// eliminamos las líneas que no encontremos en el $_POST
                foreach($lineas as $l)
                {
                   $encontrada = FALSE;
                   for($num = 0; $num <= $numlineas; $num++)
                   {
                      if( isset($_POST['idlinea_'.$num]) )
                      {
                         if($l->idlinea == intval($_POST['idlinea_'.$num]))
                         {
                            $encontrada = TRUE;
                            break;
                         }
                      }
                   }
                   if( !$encontrada )
                   {
                      if( !$l->delete() )
                         $this->new_error_msg("¡Imposible eliminar la línea del artículo ".$l->referencia."!");
                   }
                }

                /// modificamos y/o añadimos las demás líneas
                for($num = 0; $num <= $numlineas; $num++)
                {
                   $encontrada = FALSE;
                   if( isset($_POST['idlinea_'.$num]) )
                   {
                      foreach($lineas as $k => $value)
                      {
                         /// modificamos la línea
                         if($value->idlinea == intval($_POST['idlinea_'.$num]))
                         {

                            $encontrada = TRUE;
                            $cantidad_old = $value->cantidad;
                            $lineas[$k]->cantidad = floatval($_POST['cantidad_'.$num]);
                            $lineas[$k]->pvpunitario = floatval($_POST['pvp_'.$num]);
                            $lineas[$k]->dtopor = floatval($_POST['dto_'.$num]);
                            $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                            $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor)/100);
                            $lineas[$k]->descripcion = $_POST['desc_'.$num];

                            $lineas[$k]->codimpuesto = NULL;
                            $lineas[$k]->iva = 0;
                            $lineas[$k]->recargo = 0;
                            $lineas[$k]->irpf = floatval($_POST['irpf_'.$num]);
                            if( !$serie->siniva AND $this->cliente_s->regimeniva != 'Exento' )
                            {
                               $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                               if($imp0)
                                  $lineas[$k]->codimpuesto = $imp0->codimpuesto;

                               $lineas[$k]->iva = floatval($_POST['iva_'.$num]);
                               $lineas[$k]->recargo = floatval($_POST['recargo_'.$num]);
                            }

                            if( $lineas[$k]->save() )
                            {
                               $presupuesto->neto += $value->pvptotal;
                               $presupuesto->totaliva += $value->pvptotal * $value->iva/100;
                               $presupuesto->totalirpf += $value->pvptotal * $value->irpf/100;
                               $presupuesto->totalrecargo += $value->pvptotal * $value->recargo/100;


                            }
                            else
                               $this->new_error_msg("¡Imposible modificar la línea del artículo ".$value->referencia."!");
                            break;
                         }
                      }

                      /// añadimos la línea
                      if(!$encontrada AND intval($_POST['idlinea_'.$num]) == -1 AND isset($_POST['referencia_'.$num]))
                      {
                         $linea = new linea_presupuesto_cliente();
                         $linea->idpresupuesto = $presupuesto->idpresupuesto;
                         $linea->descripcion = $_POST['desc_'.$num];

                         if( !$serie->siniva AND $this->cliente_s->regimeniva != 'Exento' )
                         {
                            $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                            if($imp0)
                               $linea->codimpuesto = $imp0->codimpuesto;

                            $linea->iva = floatval($_POST['iva_'.$num]);
                            $linea->recargo = floatval($_POST['recargo_'.$num]);
                         }

                         $linea->irpf = floatval($_POST['irpf_'.$num]);
                         $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                         $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                         $linea->dtopor = floatval($_POST['dto_'.$num]);
                         $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                         $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);

                         $art0 = $articulo->get( $_POST['referencia_'.$num] );
                         if($art0)
                         {
                            $linea->referencia = $art0->referencia;
                         }

                         if( $linea->save() )
                         {

                            $presupuesto->neto += $linea->pvptotal;
                            $presupuesto->totaliva += $linea->pvptotal * $linea->iva/100;
                            $presupuesto->totalirpf += $linea->pvptotal * $linea->irpf/100;
                            $presupuesto->totalrecargo += $linea->pvptotal * $linea->recargo/100;
                         }
                         else
                            $this->new_error_msg("¡Imposible guardar la línea del artículo ".$linea->referencia."!");
                      }
                   }
                }


             }



            if($continuar)
            {
               /// redondeamos
               $presupuesto->neto = round($presupuesto->neto, FS_NF0);
               $presupuesto->totaliva = round($presupuesto->totaliva, FS_NF0);
               $presupuesto->totalirpf = round($presupuesto->totalirpf, FS_NF0);
               $presupuesto->totalrecargo = round($presupuesto->totalrecargo, FS_NF0);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva - $presupuesto->totalirpf + $presupuesto->totalrecargo;

               if( $presupuesto->save() )
               {
                  $this->new_message("<a href='./index.php?page=tpvmod&edita=presupuesto&id=".$presupuesto->idpresupuesto."'>".FS_PRESUPUESTO."</a> guardado correctamente. <a  href='index.php?page=ventas_imprimir&presupuesto=TRUE&id=".$presupuesto->idpresupuesto."'>Imprimir</a>");



                  /// actualizamos la caja
                  $this->caja->dinero_fin += $presupuesto->total;
                  $this->caja->tickets += 1;
                  $this->caja->ip = $_SERVER['REMOTE_ADDR'];
                  if( !$this->caja->save() )
                  {
                     $this->new_error_msg("¡Imposible actualizar la caja!");
                  }
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $presupuesto->delete() )
            {
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$presupuesto->url()."'>".FS_ALBARAN."</a>!");
      }
      $this->cliente_s = $this->cliente->get($this->clientedefault);
   }

     private function edita_factura_cliente()
   {
      $continuar = TRUE;
      $this->cliente_s = $this->cliente->get($_POST['cliente']);
      if( !$this->cliente_s)
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }

      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
      if( !$ejercicio )
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
      if( !$divisa )
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }

      if( isset($_POST['imprimir_desc']) )
      {
         $this->imprimir_descripciones = TRUE;
         setcookie('imprimir_desc', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_descripciones = FALSE;
         setcookie('imprimir_desc', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      if( isset($_POST['imprimir_obs']) )
      {
         $this->imprimir_observaciones = TRUE;
         setcookie('imprimir_obs', TRUE, time()+FS_COOKIES_EXPIRE);
      }
      else
      {
         $this->imprimir_observaciones = FALSE;
         setcookie('imprimir_obs', FALSE, time()-FS_COOKIES_EXPIRE);
      }

      $factura = new factura_cliente();
      $factura = $factura->get($_POST['id']);


      if( $continuar )
      {
         $factura->fecha = $_POST['fecha'];
         $factura->codalmacen = $almacen->codalmacen;
         $factura->codejercicio = $ejercicio->codejercicio;
         $factura->codserie = $serie->codserie;
         $factura->codpago = $forma_pago->codpago;
         $factura->coddivisa = $divisa->coddivisa;
         $factura->tasaconv = $divisa->tasaconv;
         $factura->codagente = $this->agente->codagente;
         $factura->observaciones = $_POST['observaciones'];
         $factura->numero2 = $_POST['numero2'];
         $factura->irpf = $serie->irpf;
         $factura->porcomision = $this->agente->porcomision;

         foreach($this->cliente_s->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $factura->codcliente = $this->cliente_s->codcliente;
               $factura->cifnif = $this->cliente_s->cifnif;
               $factura->nombrecliente = $this->cliente_s->razonsocial;
               $factura->apartado = $d->apartado;
               $factura->ciudad = $d->ciudad;
               $factura->coddir = $d->id;
               $factura->codpais = $d->codpais;
               $factura->codpostal = $d->codpostal;
               $factura->direccion = $d->direccion;
               $factura->provincia = $d->provincia;
               break;
            }
         }
            if( isset($_POST['numlineas']) )
             {
                $numlineas = intval($_POST['numlineas']);

                $factura->neto = 0;
                $factura->totaliva = 0;
                $factura->totalirpf = 0;
                $factura->totalrecargo = 0;
                $lineas = $factura->get_lineas();
                $articulo = new articulo();
                $lineas_iva = array();
                $lineas_valoriva = array();
                $lineas_totaliva = array();
                $lineas_total = array();
                /// eliminamos las líneas que no encontremos en el $_POST
                foreach($lineas as $l)
                {
                   $encontrada = FALSE;
                   for($num = 0; $num <= $numlineas; $num++)
                   {
                      if( isset($_POST['idlinea_'.$num]) )
                      {
                         if($l->idlinea == intval($_POST['idlinea_'.$num]))
                         {
                            $encontrada = TRUE;
                            break;
                         }
                      }
                   }
                   if( !$encontrada )
                   {
                      if( $l->delete() )
                      {
                         /// actualizamos el stock
                         $art0 = $articulo->get($l->referencia);
                         if($art0)
                            $art0->sum_stock($factura->codalmacen, $l->cantidad);
                      }
                      else
                         $this->new_error_msg("¡Imposible eliminar la línea del artículo ".$l->referencia."!");
                   }
                }

                /// modificamos y/o añadimos las demás líneas
                for($num = 0; $num <= $numlineas; $num++)
                {
                   $encontrada = FALSE;
                   if( isset($_POST['idlinea_'.$num]) )
                   {
                      foreach($lineas as $k => $value)
                      {
                         /// modificamos la línea
                         if($value->idlinea == intval($_POST['idlinea_'.$num]))
                         {

                            $encontrada = TRUE;
                            $cantidad_old = $value->cantidad;
                            $lineas[$k]->cantidad = floatval($_POST['cantidad_'.$num]);
                            $lineas[$k]->pvpunitario = floatval($_POST['pvp_'.$num]);
                            $lineas[$k]->dtopor = 0;
                            $lineas[$k]->pvpsindto = ($value->cantidad * $value->pvpunitario);
                            $lineas[$k]->pvptotal = ($value->cantidad * $value->pvpunitario * (100 - $value->dtopor)/100);
                            $lineas[$k]->descripcion = $_POST['desc_'.$num];

                            $lineas[$k]->codimpuesto = NULL;
                            $lineas[$k]->iva = 0;
                            $lineas[$k]->recargo = 0;
                            $lineas[$k]->irpf = floatval($_POST['irpf_'.$num]);
                            if( !$serie->siniva AND $this->cliente_s->regimeniva != 'Exento' )
                            {
                               $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                               if($imp0)
                                  $lineas[$k]->codimpuesto = $imp0->codimpuesto;

                               $lineas[$k]->iva = floatval($_POST['iva_'.$num]);
                               $lineas[$k]->recargo = floatval($_POST['recargo_'.$num]);
                            }

                            if( $lineas[$k]->save() )
                            {
                               if(!isset($lineas_iva[$lineas[$k]->codimpuesto])){
                                   $lineas_iva[$lineas[$k]->codimpuesto] = 0;
                                   $lineas_valoriva[$lineas[$k]->codimpuesto] = 0;
                                   $lineas_totaliva[$lineas[$k]->codimpuesto] = 0;
                                   $lineas_total[$lineas[$k]->codimpuesto] = 0;
                               }
                               $lineas_iva[$lineas[$k]->codimpuesto] += $lineas[$k]->pvptotal;
                               $lineas_valoriva[$lineas[$k]->codimpuesto] += $lineas[$k]->iva;
                               $lineas_totaliva[$lineas[$k]->codimpuesto] += ($lineas[$k]->pvptotal * ($lineas[$k]->iva/100));
                               $lineas_total[$lineas[$k]->codimpuesto] += ($lineas[$k]->pvptotal * (($lineas[$k]->iva/100)+1));
                               $factura->neto += $value->pvptotal;
                               $factura->totaliva += $value->pvptotal * $value->iva/100;
                               $factura->totalirpf += $value->pvptotal * $value->irpf/100;
                               $factura->totalrecargo += $value->pvptotal * $value->recargo/100;

                               if($lineas[$k]->cantidad != $cantidad_old)
                               {
                                  /// actualizamos el stock
                                  $art0 = $articulo->get($value->referencia);
                                  if($art0)
                                     $art0->sum_stock($factura->codalmacen, $cantidad_old - $lineas[$k]->cantidad);
                               }
                            }
                            else
                               $this->new_error_msg("¡Imposible modificar la línea del artículo ".$value->referencia."!");
                            break;
                         }
                      }

                      /// añadimos la línea
                      if(!$encontrada AND intval($_POST['idlinea_'.$num]) == -1 AND isset($_POST['referencia_'.$num]))
                      {
                         $linea = new linea_factura_cliente();
                         $linea->idfactura = $factura->idfactura;
                         $linea->descripcion = $_POST['desc_'.$num];

                         if( !$serie->siniva AND $this->cliente_s->regimeniva != 'Exento' )
                         {
                            $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$num]);
                            if($imp0)
                               $linea->codimpuesto = $imp0->codimpuesto;

                            $linea->iva = floatval($_POST['iva_'.$num]);
                            $linea->recargo = floatval($_POST['recargo_'.$num]);
                         }

                         $linea->irpf = floatval($_POST['irpf_'.$num]);
                         $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                         $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                         $linea->dtopor = floatval($_POST['dto_'.$num]);
                         $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                         $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);

                         $art0 = $articulo->get( $_POST['referencia_'.$num] );
                         if($art0)
                         {
                            $linea->referencia = $art0->referencia;
                         }

                         if( $linea->save() )
                         {
                             if(!isset($lineas_iva[$linea->codimpuesto])){
                                $lineas_iva[$linea->codimpuesto] = 0;
                                $lineas_valoriva[$linea->codimpuesto] = 0;
                                $lineas_totaliva[$linea->codimpuesto] = 0;
                                $lineas_total[$linea->codimpuesto] = 0;
                            }
                            $lineas_iva[$linea->codimpuesto] += $linea->pvptotal;
                            $lineas_valoriva[$linea->codimpuesto] += $linea->iva;
                            $lineas_totaliva[$linea->codimpuesto] += ($linea->pvptotal * ($linea->iva/100));
                            $lineas_total[$linea->codimpuesto] += ($linea->pvptotal * (($linea->iva/100))+1);
                            if($art0)
                            {
                               /// actualizamos el stock
                               $art0->sum_stock($factura->codalmacen, 0 - $linea->cantidad);
                            }

                            $factura->neto += $linea->pvptotal;
                            $factura->totaliva += $linea->pvptotal * $linea->iva/100;
                            $factura->totalirpf += $linea->pvptotal * $linea->irpf/100;
                            $factura->totalrecargo += $linea->pvptotal * $linea->recargo/100;
                         }
                         else
                            $this->new_error_msg("¡Imposible guardar la línea del artículo ".$linea->referencia."!");
                      }
                   }
                }


             }



            if($continuar)
            {
               /// redondeamos
               $factura->neto = round($factura->neto, FS_NF0);
               $factura->totaliva = round($factura->totaliva, FS_NF0);
               $factura->totalirpf = round($factura->totalirpf, FS_NF0);
               $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
               $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;

               if( $factura->save() )
               {
                  $factura->get_lineas_iva();
                  //Actualizamos las lineas del IVA
                  /*$lista_lineas_iva = new linea_iva_factura_cliente();
                  $lineasiva0 = $lista_lineas_iva->all_from_factura($factura->idfactura);
                  foreach($lineasiva0 as $linea){
                    if($lineas_iva[$linea->codimpuesto]){
                        $linea->neto = $lineas_iva[$linea->codimpuesto];
                        $linea->totaliva = $lineas_totaliva[$linea->codimpuesto];
                        $linea->totallinea = $lineas_total[$linea->codimpuesto];
                        $linea->save();
                    }else{
                        $linea->delete();
                    }
                  }*/
                  $this->new_message("<a href='./index.php?page=tpvmod&edita=factura&id=".$factura->idfactura."'>".FS_FACTURA."</a> guardado correctamente. <a  href='index.php?page=factura_detallada&id=".$factura->idfactura."'>Imprimir</a>");

                  /// actualizamos la caja
                  $this->caja->dinero_fin += $factura->total;
                  $this->caja->tickets += 1;
                  $this->caja->ip = $_SERVER['REMOTE_ADDR'];
                  if( !$this->caja->save() )
                  {
                     $this->new_error_msg("¡Imposible actualizar la caja!");
                  }
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$factura->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $factura->delete() )
            {
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$factura->url()."'>".FS_ALBARAN."</a>!");
      }
      $this->cliente_s = $this->cliente->get($this->clientedefault);
   }

   private function abrir_caja()
   {
      if($this->user->admin)
      {
         if($this->terminal)
         {
            $this->terminal->abrir_cajon();
            $this->terminal->save();
         }
      }
      else
         $this->new_error_msg('Sólo un administrador puede abrir la caja.');
   }



   private function cerrar_caja()
   {
      $this->caja->fecha_fin = Date('d-m-Y H:i:s');
      if( $this->caja->save() )
      {
         if( $this->terminal )
         {
            $this->terminal->add_linea_big("\nCIERRE DE CAJA:\n");
            $this->terminal->add_linea("Empleado: ".$this->user->codagente." ".$this->agente->get_fullname()."\n");
            $this->terminal->add_linea("Caja: ".$this->caja->fs_id."\n");
            $this->terminal->add_linea("Fecha inicial: ".$this->caja->fecha_inicial."\n");
            $this->terminal->add_linea("Dinero inicial: ".$this->show_precio($this->caja->dinero_inicial, FALSE, FALSE)."\n");
            $this->terminal->add_linea("Fecha fin: ".$this->caja->show_fecha_fin()."\n");
            $this->terminal->add_linea("Dinero fin: ".$this->show_precio($this->caja->dinero_fin, FALSE, FALSE)."\n");
            $this->terminal->add_linea("Diferencia: ".$this->show_precio($this->caja->diferencia(), FALSE, FALSE)."\n");
            $this->terminal->add_linea("Tickets: ".$this->caja->tickets."\n\n");
            $this->terminal->add_linea("Dinero pesado:\n\n\n");
            $this->terminal->add_linea("Observaciones:\n\n\n\n");
            $this->terminal->add_linea("Firma:\n\n\n\n\n\n\n");

            /// encabezado común para los tickets
            $this->terminal->add_linea_big( $this->terminal->center_text($this->empresa->nombre, 16)."\n");
            $this->terminal->add_linea( $this->terminal->center_text($this->empresa->lema) . "\n\n");
            $this->terminal->add_linea( $this->terminal->center_text($this->empresa->direccion . " - " . $this->empresa->ciudad) . "\n");
            $this->terminal->add_linea( $this->terminal->center_text("CIF: " . $this->empresa->cifnif) . chr(27).chr(105) . "\n\n"); /// corta el papel
            $this->terminal->add_linea( $this->terminal->center_text($this->empresa->horario) . "\n");

            $this->terminal->abrir_cajon();
            $this->terminal->save();

            /// recargamos la página
            header('location: '.$this->url().'&terminal='.$this->terminal->id);
         }
         else
         {
            /// recargamos la página
            header('location: '.$this->url());
         }
      }
      else
         $this->new_error_msg("¡Imposible cerrar la caja!");
   }

   private function reimprimir_ticket()
   {
      $albaran = new albaran_cliente();

      if($_GET['reticket'] == '')
      {
         foreach($albaran->all() as $alb)
         {
            $alb0 = $alb;
            break;
         }
      }
      else
         $alb0 = $albaran->get_by_codigo($_GET['reticket']);

      if($alb0)
      {
         $this->imprimir_ticket($alb0, 1, FALSE);
      }
      else
         $this->new_error_msg("Ticket no encontrado.");
   }

   private function borrar_ticket()
   {
      $albaran = new albaran_cliente();
      $alb = $albaran->get_by_codigo($_GET['delete']);
      if($alb)
      {
         if($alb->ptefactura)
         {
            $articulo = new articulo();
            foreach($alb->get_lineas() as $linea)
            {
               $art0 = $articulo->get($linea->referencia);
               if($art0)
               {
                  $art0->sum_stock($alb->codalmacen, $linea->cantidad);
                  $art0->save();
               }
            }

            if( $alb->delete() )
            {
               $this->new_message("Ticket ".$_GET['delete']." borrado correctamente.");

               /// actualizamos la caja
               $this->caja->dinero_fin -= $alb->total;
               $this->caja->tickets -= 1;
               if( !$this->caja->save() )
               {
                  $this->new_error_msg("¡Imposible actualizar la caja!");
               }
            }
            else
               $this->new_error_msg("¡Imposible borrar el ticket ".$_GET['delete']."!");
         }
         else
            $this->new_error_msg('No se ha podido borrar este '.FS_ALBARAN.' porque ya está facturado.');
      }
      else
         $this->new_error_msg("Ticket no encontrado.");
   }


   public function tipos_a_guardar()
   {
      $tipos = array();

      if( $this->user->have_access_to('ventas_presupuesto') )
      {
          $this->tipo="presupuesto";
         $tipos[] = array('tipo' => 'presupuesto', 'nombre' => ucfirst(FS_PRESUPUESTO).' para cliente');
      }

      if( $this->user->have_access_to('ventas_pedido') )
         $tipos[] = array('tipo' => 'pedido', 'nombre' => ucfirst(FS_PEDIDO).' de cliente');

      if( $this->user->have_access_to('ventas_albaran') )
         $tipos[] = array('tipo' => 'albaran', 'nombre' => ucfirst(FS_ALBARAN).' de cliente');

      if( $this->user->have_access_to('ventas_factura') )
         $tipos[] = array('tipo' => 'factura', 'nombre' => 'Factura de cliente');

      return $tipos;
   }

}
