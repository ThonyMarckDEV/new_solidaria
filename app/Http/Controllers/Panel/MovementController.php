<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Http\Requests\StoreMovementRequest;
use App\Http\Requests\UpdateMovementRequest;
use App\Http\Resources\MovementResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class MovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Movement::class);
        return Inertia::render('panel/movement/indexMovement');
    }

     /**
     * List movements with optional filtering
     */
    public function listMovements(Request $request)
    {
        Gate::authorize('viewAny', Movement::class);
        try {
            $codigo = $request->get('codigo');
            $movements = Movement::with(['supplier', 'user', 'typemovement', 'product_movements'])
                ->when($codigo, function ($query, $codigo) {
                    return $query->where('code', 'like', "%$codigo%");
                })
                ->orderBy('id', 'asc')
                ->paginate(15);

            return response()->json([
                'movements' => MovementResource::collection($movements),
                'pagination' => [
                    'total' => $movements->total(),
                    'current_page' => $movements->currentPage(),
                    'per_page' => $movements->perPage(),
                    'last_page' => $movements->lastPage(),
                    'from' => $movements->firstItem(),
                    'to' => $movements->lastItem()
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al listar los movimientos',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Movement::class);
        return Inertia::render('panel/movement/components/formMovement');
    }

    // /**
    //  * Store a newly created resource in storage.
    //  */
    public function store(StoreMovementRequest $request)
    {
        Gate::authorize('create', Movement::class);
        $validated = $request->validated();
        $movement = Movement::create($validated);

        return redirect()->route('panel.movements.index')->with('message', 'Movimiento creado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Movement $movement)
    {
        Gate::authorize('view', $movement);
        return response()->json([
            'state' => true,
            'message' => 'Movimiento encontrado',
            'movement' => new MovementResource($movement),
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Movement $movement)
    {
        Gate::authorize('update', $movement);
        return Inertia::render('panel/movement/components/formMovement', [
            'movement' => new MovementResource($movement)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMovementRequest $request, Movement $movement)
    {
        Gate::authorize('update', $movement);
        $validated = $request->validated();
        $movement->update($validated);

        return response()->json([
            'state' => true,
            'message' => 'Movimiento actualizado de manera correcta',
            'movement' => new MovementResource($movement->refresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Movement $movement)
    {
        Gate::authorize('delete', $movement);
        $movement->delete();

        return response()->json([
            'state' => true,
            'message' => 'Movimiento eliminado de manera correcta',
        ]);
    }

    public function print(Movement $movement)
    {
        try {
            $movement->load('typemovement', 'supplier', 'user', 'product_movements.product');

            // Initialize IGV rate and totals
            $tasaIgv = 0.18;
            $totalSubtotal = 0;
            $totalIgv = 0;
            $totalTotal = 0;

            // HTML content for the PDF - Simple Receipt Style
            $html = '
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Factura Electrónica - ' . htmlspecialchars($movement->code) . '</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                        line-height: 1.4;
                        margin: 0;
                        padding: 20px;
                    }
                    
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                    }
                    
                    .header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    
                    .header h1 {
                        font-size: 16px;
                        font-weight: bold;
                        margin: 0;
                        padding: 0;
                    }
                    
                    .header h2 {
                        font-size: 14px;
                        font-weight: bold;
                        margin: 5px 0;
                        padding: 0;
                    }
                    
                    .divider {
                        border-top: 1px solid #000;
                        margin: 10px 0;
                    }
                    
                    .info-row {
                        margin: 5px 0;
                    }
                    
                    .info-row .label {
                        font-weight: bold;
                    }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 15px 0;
                    }
                    
                    table th, table td {
                        padding: 5px;
                        text-align: left;
                    }
                    
                    table th:nth-child(2),
                    table td:nth-child(2),
                    table th:nth-child(3),
                    table td:nth-child(3),
                    table th:nth-child(4),
                    table td:nth-child(4) {
                        text-align: right;
                    }
                    
                    .totals {
                        margin-top: 10px;
                    }
                    
                    .totals div {
                        text-align: right;
                        margin: 3px 0;
                    }
                    
                    .totals .total {
                        font-weight: bold;
                    }
                    
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        font-size: 11px;
                    }
                    
                    .seller {
                        margin-top: 20px;
                        border-top: 1px solid #000;
                        padding-top: 10px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>FACTURA DE COMPRA</h1>
                        <h2>' . htmlspecialchars($movement->code) . '</h2>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="info-row">
                        <span class="label">Proveedor. :</span> ' . htmlspecialchars($movement->supplier->name) . '
                    </div>
                    <div class="info-row">
                        <span class="label">RUC :</span> ' . htmlspecialchars($movement->supplier->ruc ?? "—") . '
                    </div>
                    <div class="info-row">
                        <span class="label">Fecha :</span> ' . Carbon::parse($movement->issue_date)->format('d/m/Y H:i:s') . '
                    </div>
                    
                    <div class="divider"></div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cant.</th>
                                <th>Precio</th>
                                <th>Importe</th>
                            </tr>
                        </thead>
                        <tbody>';

            if ($movement->product_movements->isEmpty()) {
                $html .= '<tr><td colspan="4" style="text-align: center;">No hay productos asociados.</td></tr>';
            } else {
                foreach ($movement->product_movements as $productMovement) {
                    $product = $productMovement->product;
                    $totalPrice = $productMovement->total_price ?? ($productMovement->quantity * $productMovement->unit_price);

                    // Calculate subtotal, IGV, and total based on igv_status
                    if ($movement->igv_status == 1) {
                        // IGV included in total_price
                        $subtotal = $totalPrice / (1 + $tasaIgv);
                        $igv = $totalPrice - $subtotal;
                        $total = $totalPrice;
                    } else {
                        // IGV not included
                        $subtotal = $totalPrice;
                        $igv = $subtotal * $tasaIgv;
                        $total = $subtotal + $igv;
                    }

                    // Accumulate totals
                    $totalSubtotal += $subtotal;
                    $totalIgv += $igv;
                    $totalTotal += $total;

                    // Display unit price adjusted for IGV status
                    $displayUnitPrice = $movement->igv_status == 1 ? $productMovement->unit_price / (1 + $tasaIgv) : $productMovement->unit_price;

                    $html .= '
                        <tr>
                            <td>' . htmlspecialchars($product->name) . '</td>
                            <td>' . number_format($productMovement->quantity, 1) . '</td>
                            <td>S/ ' . number_format($displayUnitPrice, 2) . '</td>
                            <td>S/ ' . number_format($subtotal, 2) . '</td>
                        </tr>';
                }
            }

            $html .= '
                        </tbody>
                    </table>
                    
                    <div class="divider"></div>
                    
                    <div class="totals">
                        <div>SUB TOTAL: S/ ' . number_format($totalSubtotal, 2) . '</div>
                        <div>IGV: S/ ' . number_format($totalIgv, 2) . '</div>
                        <div class="total">TOTAL VENTA: S/ ' . number_format($totalTotal, 2) . '</div>
                    </div>
                    
                    <div class="seller">
                        <div>COMPRADOR(A): ' . htmlspecialchars($movement->user->name) . '</div>
                    </div>
                    
                    <div class="footer">
                        <p>Representación impresa de la factura de compra</p>
                    </div>
                </div>
            </body>
            </html>';

            $pdf = Pdf::loadHTML($html);
            return $pdf->stream('factura_' . $movement->code . '.pdf');
        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }
}
