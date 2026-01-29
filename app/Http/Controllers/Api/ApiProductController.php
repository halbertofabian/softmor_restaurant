<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;

class ApiProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Require branch_id parameter
        $branchId = $request->input('branch_id');
        
        if (!$branchId) {
            return response()->json([
                'status' => 'error',
                'message' => 'branch_id es requerido'
            ], 400);
        }
        
        // Verify user has access to this branch
        $hasAccess = $user->branches()->where('branches.id', $branchId)->exists();
        
        if (!$hasAccess) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }
        
        // Filter categories and products by tenant and branch
        $categories = Category::where('tenant_id', $user->tenant_id)
                              ->where('status', true)
                              ->orderBy('name')
                              ->get();
        
        $data = $categories->map(function($category) use ($user, $branchId) {
            // Get products for this category filtered by branch and tenant
            $products = Product::where('category_id', $category->id)
                              ->where('tenant_id', $user->tenant_id)
                              ->where('branch_id', $branchId)
                              ->where('status', true)
                              ->get()
                              ->map(function($product) {
                                  return [
                                      'id' => $product->id,
                                      'name' => $product->name,
                                      'price' => $product->price,
                                      'image' => $product->image,
                                      'description' => $product->description,
                                      'controls_inventory' => $product->controls_inventory,
                                      'stock' => $product->stock,
                                      'preparation_area_id' => $product->preparation_area_id
                                  ];
                              });
            
            return [
                'id' => $category->id,
                'name' => $category->name,
                'products' => $products
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}
