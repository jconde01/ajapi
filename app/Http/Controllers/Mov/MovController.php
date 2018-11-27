<?php

namespace App\Http\Controllers\Mov;

use App\Item;
use App\Client;
use App\CliCon;
use App\Movtos;
use App\MovHeader;
use App\MovDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class MovController extends Controller
{

    public const FACTURA = 'VTA.FAC';
    public const REMISION = 'VTA.REM';
    public const VENTA_WEB = 3;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $movH = new MovHeader();
        DB::transaction(function () use($request,$movH) {
            $folio = Movtos::where('mov_type',$this::REMISION)->first();
            $folio->Folio_Sig++;
            $movH->mov_type = $this::REMISION;
            $movH->folio = $folio->Folio_Sig;
            $movH->estatus = 'Concluido';
            $movH->Origen_Modulo = "shopif";
            $movH->Origen_Mov = $request->body['id_compra'];
            $movH->Descuentos = $request->body['descuento_total'];
            //$movH->desc_porc = $request->input->body('descuento_porcentaje_total'); // Manejaremos descuento por renglon
            $email = explode('@', $request->body["comprador"]["email"]);
            $movH->entregado_por = $email[0];
            //$movH->transportado_por = $email[1];
            $movH->Importe_Total = $request->body['importe_total'];
            $movH->cliente_id = 1;
            if ($request->body['forma_pago'] == 1) {
                $movH->c_FormaPago = "01";              // Efectivo (OXXO)
            } else {
                $movH->c_FormaPago = "04";              // Tarjeta de crédito, Paypal
            }            
            $movH->save();
            foreach ($request->body["productos"] as $key => $product) {
                 $movD = New MovDetail();
                 $movD->idmov_h = $movH->idmov_h;
                 $itemID = Item::where('item_sku',$product['sku'])->select('item_id')->first();  // !!!!!! try .... catch ???
                 $movD->item_id = $itemID->item_id;
                 $movD->Cantidad = $product["cantidad"];
                 $movD->Precio_U = $product["costo_unitario"];
                 $movD->Descto = $product["descuento"] / $product["costo_unitario"] * 100;
                 $movD->almacen_ID = 1;         // Almacen GALERIA PROPIO
                 $movD->Partida = "GENERAL";
                 $movD->Orden = $key;
                 $movD->save();
            }
            $folio->save();
        });
        return response()->json(['estatus' => 'movimiento ingresado exitosamente con el folio','folio' => $movH->folio], 200);
        // return response()->json(['estatus' => 'none','data' => $movtos], 200);
    }


    public function makeInvoice(Request $request)
    {
        $movH = new MovHeader();
        DB::transaction(function () use($request,$movH) {

            $id_compra = $request->body['id_compra'];
            // Localiza la compra entre las remisiones guardadas
            // para obtener la información para generar la factura
            try {
                $compra = MovHeader::where('mov_type',$this::REMISION)->where('Origen_Mov',$id_compra)->firstOrFail();
                $detail = MovDetail::where('idmov_h',$compra->idmov_h)->get();
            } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                response()->json(['estatus' => 'Error. No pude encontrar la compra relacionada','id_compra' => $id_compra], 500);
            }
            
            // Busca un cliente con el RFC pasado. Si no existe lo agrega a la BD
            try {
                $cliente = Client::where('RFC',$request->body['rfc'])->firstOrFail();
                $cliCon = CliCon::find($cliente->cliente_id);    
            } catch ( Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
                // Agrega el cliente a la BD
                $cliente = New Client();
                $cliente->Nombre = $request->body['razon_social'];
                $cliente->RFC = $request->body['rfc'];
                $cliente->CP = $request->body['CP'];
                $cliente->c_UsoCFDI = $request->body['cfdi'];
                $cliente->save();
                $cliCon = New CliCon();
                $cliCon->NOMCON = 'contacto 1';
                $cliCon->EMACON = $compra->entregado_por . '@' . $compra->transportado_por;
                $cliCon->save();
            }

            // Ya tenemos el cliente y la compra relacionada. 
            // Procedemos a crear la factura
            $folio = Movtos::where('mov_type',$this::FACTURA)->first();
            $folio->Folio_Sig++;
            $movH->mov_type = $this::FACTURA;
            $movH->folio = $folio->Folio_Sig;
            $movH->estatus = 'Concluido';
            $movH->referencia = "";
            $movH->Notas = "Venta por Internet";
            $movH->Origen = $this::VENTA_WEB;
            $movH->Origen_Modulo = "shopif";
            $movH->Origen_Mov = $request->body['id_compra'];
            $movH->Descuentos = $compra->Descuentos;
            //$movH->desc_porc = $request->input('descuento_porcentaje_total'); // Manejaremos descuento por renglon
            $movH->Importe_Total = $compra->Importe_Total;
            $movH->cliente_id = $cliente->cliente_id;
            $movH->SinExistencias = 2;              // Factura CLASE B
            $movH->Remision = $compra->idmov_h;     // Remisión asociada
            $movH->c_UsoCFDI = $request->body['cfdi'];
            $movH->c_MetodoPago = "PUE";
            $movH->c_FormaPago = $compra->c_FormaPago;
            $movH->save();
            foreach ($detail as $key => $product) {
                 $movD = New MovDetail();
                 $movD->idmov_h = $movH->idmov_h;
                 $movD->item_id = $product['item_id'];
                 $movD->Cantidad = $product["Cantidad"];
                 $movD->Precio_U = $product["Precio_U"];
                 $movD->Descto = $product["Descto"];
                 $movD->almacen_ID = 1;         // Almacen GALERIA PROPIO
                 $movD->Partida = "GENERAL";
                 $movD->Orden = $key;
                 $movD->save();
            }
            $folio->save();
        });
        return response()->json(['estatus' => 'factura generada exitósamente con el folio','folio' => $movH->folio], 200);        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
