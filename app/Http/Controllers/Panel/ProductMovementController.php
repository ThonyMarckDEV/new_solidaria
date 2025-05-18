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
            // Llamar al pipeline, pasando los datos del request y un movement_id
            $result = $this->pipeline->handle($request->all(), 1);

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