<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductMovementController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
            'fraction_quantity' => 'required|integer|min:0',
            'total_price' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'batch' => 'required|string|max:15',
            'expiry_date' => 'required|date',
            'quantity_type' => 'required|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $movement = ProductMovement::create([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'fraction_quantity' => $request->fraction_quantity,
                'total_price' => $request->total_price,
                'unit_price' => $request->unit_price,
                'fraction_price' => $request->fraction_quantity > 0 ? $request->unit_price : 0,
                'batch' => $request->batch,
                'expiry_date' => $request->expiry_date,
                'movement_id' => 1, // Assuming a default movement_id; adjust as needed
                'quantity_type' => $request->quantity_type,
                'status' => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product movement created successfully',
                'data' => $movement,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating product movement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}