<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\PrescriptionItem;
use Illuminate\Support\Facades\DB;

class PrescriptionItemController extends Controller
{
    public function saveItems(Request $request, $id)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1'
            ]);

            $items = $request->input('items');
            
            // Clear existing items for this prescription
            PrescriptionItem::where('prescription_id', $id)->delete();
            
            // Insert new items
            foreach ($items as $item) {
                PrescriptionItem::create([
                    'prescription_id' => $id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Products saved successfully!'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', collect($e->errors())->flatten()->toArray())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving prescription items: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving items: ' . $e->getMessage()
            ], 500);
        }
    }
   public function getItems($id)
{
    try {
        $items = DB::table('prescription_items')
            ->join('products', 'prescription_items.product_id', '=', 'products.id')
            ->where('prescription_items.prescription_id', $id)
            ->select(
                'prescription_items.product_id',
                'prescription_items.quantity',
                'products.product_name',
                'products.sale_price as product_price'
            )
            ->get();

        return response()->json([
            'success' => true,
            'items' => $items
        ]);

    } catch (\Exception $e) {
        Log::error('Error loading prescription items: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error loading items: ' . $e->getMessage()
        ], 500);
    }
}
}