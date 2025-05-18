<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Pipelines\Movement\StoreProductMovementPipeline;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductMovementController extends Controller
{
    protected $pipeline;

    public function __construct(StoreProductMovementPipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function store(Request $request)
    {
        try {
            // Validate the request to ensure movement_id is provided
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:0',
                'fraction_quantity' => 'required|integer|min:0',
                'total_price' => 'required|numeric|min:0',
                'unit_price' => 'required|numeric|min:0',
                'batch' => 'required|string',
                'expiry_date' => 'required|date',
                'quantity_type' => 'required|in:0,1,2',
                'movement_id' => 'required|integer|exists:movements,id', // Validate movement_id
            ]);

            // Pass the validated data, including movement_id, to the pipeline
            $result = $this->pipeline->handle($validated, $validated['movement_id']);

            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el movimiento de producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}