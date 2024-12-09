<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use Illuminate\Http\Request;

class InventoryStockController extends Controller
{
    public function index()
    {
        $stocks = InventoryStock::with(['variant.product.category'])
        ->orderByDesc('id')
        ->paginate(10);
        return response()->json([
            'message' => 'Danh sách tồn kho',
            'data' => $stocks
        ]);
    }
}
