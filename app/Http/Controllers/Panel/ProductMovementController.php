<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\ProductMovement;
use App\Pipelines\Movement\StoreProductMovementPipeline;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductMovementController extends Controller
{
    protected $pipeline;

    public function __construct(StoreProductMovementPipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function getProductMovements($movementId)
    {
        try {

            $movement = Movement::with('product_movements.product.laboratory')->findOrFail($movementId);


            $productMovements = $movement->product_movements ?? collect([]);

            Log::info('Product movements for movement_id ' . $movementId, ['count' => $productMovements->count(), 'data' => $productMovements->toArray()]);


            $formattedMovements = $productMovements->map(function ($pm) {
                return [
                    'id' => $pm->id,
                    'productId' => $pm->product_id,
                    'quantity' => $pm->quantity,
                    'fractionQuantity' => $pm->fraction_quantity,
                    'unitPrice' => number_format($pm->unit_price, 2),
                    'unitPriceEx' => number_format($pm->unit_price, 2),
                    'fractionPrice' => number_format($pm->fraction_price, 2),
                    'totalPrice' => number_format($pm->total_price, 2),
                    'labName' => $pm->product->laboratory->name ?? 'N/A',
                    'productName' => $pm->product->name ?? 'Unknown',
                    'unitPrices' => number_format($pm->unit_price, 2) . ' - ' . number_format($pm->fraction_price, 2),
                    'batch' => $pm->batch,
                    'expiryDate' => $pm->expiry_date,
                    'expiryDateDisplay' => \Carbon\Carbon::parse($pm->expiry_date)->format('d-m-Y'),
                    'movementId' => $pm->movement_id,
                    'movementTypeId' => $pm->type_movement_id ?? 1,
                    'quantityStatus' => $pm->quantity_type,
                    'quantityType' => $pm->quantity_type === 1 ? 'Box' : ($pm->quantity_type === 0 ? 'Fraction' : 'Both'),
                    'totalQuantity' => (string) ($pm->quantity + $pm->fraction_quantity),
                    'generalPrice' => number_format($pm->unit_price, 2) . ' - ' . number_format($pm->fraction_price, 2),
                    'status' => $pm->status ?? 1,
                ];
            });

            // Calculate totals respecting igv_status
            $tasaIgv = 0.18;
            $totalSubtotal = 0;
            $totalIgv = 0;
            $totalTotal = 0;

            foreach ($productMovements as $pm) {
                $totalPrice = $pm->total_price ?? 0;

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

                $totalSubtotal += $subtotal;
                $totalIgv += $igv;
                $totalTotal += $total;
            }

            // Log the formatted response
            Log::info('Formatted product movements response', ['movement_id' => $movementId, 'data' => $formattedMovements->toArray()]);

            return response()->json([
                'success' => true,
                'message' => 'Product movements fetched successfully',
                'data' => $formattedMovements,
                'subtotal' => number_format($totalSubtotal, 2),
                'tax' => number_format($totalIgv, 2),
                'total' => number_format($totalTotal, 2),
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('Movement not found', ['movement_id' => $movementId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Movement not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching product movements', ['movement_id' => $movementId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error fetching product movements',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

   public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:0',
                'fraction_quantity' => 'required|integer|min:0',
                'total_price' => 'required|numeric|min:0',
                'unit_price' => 'required|numeric|min:0',
                'batch' => 'required|string',
                'expiry_date' => 'required|date',
                'quantity_type' => 'required|in:0,1,2',
                'movement_id' => 'required|integer|exists:movements,id',
            ]);

            $result = $this->pipeline->handle($validated, $validated['movement_id']);

            // Access the ProductMovement model from $result['data']
            $movement = $result['data'];

            return response()->json([
                'success' => true,
                'message' => 'Product movement created successfully',
                'data' => [
                    'id' => $movement->id, // Use ->id since $movement is a model
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'fraction_quantity' => $validated['fraction_quantity'],
                    'total_price' => $validated['total_price'],
                    'unit_price' => $validated['unit_price'],
                    'batch' => $validated['batch'],
                    'expiry_date' => $validated['expiry_date'],
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating product movement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $productMovement = ProductMovement::findOrFail($id);
            $productMovement->delete();
            return response()->json([
                'success' => true,
                'message' => 'Product movement deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product movement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
}