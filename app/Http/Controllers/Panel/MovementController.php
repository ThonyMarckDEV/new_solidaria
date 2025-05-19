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

        // Current date and time for generation
        $generationDateTime = Carbon::now()->format('d/m/Y H:i:s');

        // HTML content for the PDF - Enhanced Receipt Style
        $html = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Comprobante Electrónico - ' . htmlspecialchars($movement->code) . '</title>
            <style>
                body {
                    font-family: Helvetica, Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.5;
                    margin: 0;
                    padding: 20px;
                    background-color: #f9f9f9;
                }
                
                .container {
                    max-width: 650px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                }
                
                .top-info {
                    display: flex;
                    justify-content: flex-end;
                    margin-bottom: 10px;
                }
                
                .generation-datetime {
                    font-size: 11px;
                    color: #555;
                }
                
                .header {
                    text-align: center;
                    background-color: #f5f5f5;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                
                .logo-container {
                    margin-bottom: 15px;
                }
                
                .logo-container img {
                    max-height: 100px;
                    width: auto;
                }
                
                .company-title {
                    font-size: 28px;
                    font-weight: bold;
                    color: #1a3c6d;
                    margin: 10px 0;
                }
                
                .header h1 {
                    font-size: 18px;
                    font-weight: bold;
                    margin: 0;
                    color: #333;
                }
                
                .header h2 {
                    font-size: 14px;
                    font-weight: bold;
                    margin: 5px 0;
                    color: #555;
                }
                
                .divider {
                    border-top: 2px solid #e0e0e0;
                    margin: 15px 0;
                }
                
                .info-row {
                    margin: 8px 0;
                    display: flex;
                    justify-content: space-between;
                }
                
                .info-row .label {
                    font-weight: bold;
                    color: #333;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    border: 1px solid #e0e0e0;
                }
                
                table th, table td {
                    padding: 8px;
                    text-align: left;
                    border-bottom: 1px solid #e0e0e0;
                }
                
                table th {
                    background-color: #f0f0f0;
                    font-weight: bold;
                    color: #333;
                }
                
                table tr:nth-child(even) {
                    background-color: #fafafa;
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
                    margin-top: 15px;
                    background-color: #f5f5f5;
                    padding: 10px;
                    border-radius: 5px;
                }
                
                .totals div {
                    text-align: right;
                    margin: 5px 0;
                    font-size: 13px;
                    color: #333;
                }
                
                .totals .total {
                    font-weight: bold;
                    font-size: 14px;
                    color: #1a3c6d;
                }
                
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 11px;
                    color: #777;
                }
                
                .seller {
                    margin-top: 20px;
                    border-top: 1px solid #e0e0e0;
                    padding-top: 10px;
                    font-size: 12px;
                    color: #333;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="top-info">
                    <div class="generation-datetime">Generado: ' . htmlspecialchars($generationDateTime) . '</div>
                </div>
                <div class="header">
                    <div class="logo-container">
                        <img src="' . public_path('images/boticas-solidaria-logo.png') . '" alt="Boticas Solidaria Logo">
                    </div>
                    <div class="company-title">BOTICAS SOLIDARIA</div>
                    <h1>COMPROBANTE DE COMPRA</h1>
                    <h2>' . htmlspecialchars($movement->code) . '</h2>
                </div>
                
                <div class="divider"></div>
                
                <div class="info-row">
                    <span class="label">Proveedor:</span>
                    <span>' . htmlspecialchars($movement->supplier->name) . '</span>
                </div>
                <div class="info-row">
                    <span class="label">RUC:</span>
                    <span>' . htmlspecialchars($movement->supplier->ruc ?? "—") . '</span>
                </div>
                <div class="info-row">
                    <span class="label">Fecha:</span>
                    <span>' . Carbon::parse($movement->issue_date)->format('d/m/Y H:i:s') . '</span>
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
                    <p>Representación impresa del comprobante de compra</p>
                </div>
            </div>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($html);
        return $pdf->stream('comprobante_' . $movement->code . '.pdf');
    } catch (\Exception $e) {
        Log::error('PDF generation failed: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
    }
}
}
