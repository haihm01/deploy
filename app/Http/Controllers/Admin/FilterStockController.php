<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class FilterStockController extends Controller
{
    public function getCategoryFilter()
    {
        $categories = Category::all();

        $data = $categories->map(function ($category) {
            return [
                'name' => $category->name,
                'slug' => $category->slug,
            ];
        });

        return $data;
    }


    public function filterStock(Request $request)
    {
        $search = $request->input('search');
        $categories = $request->input('categories', []);
        $filters = $request->input('filter'); 

        $query = Product::query();
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if (!empty($categories)) {
            $query->whereHas('category', function ($q) use ($categories) {
                $q->whereIn('slug', $categories);
            });
        }
        // Xử lý các bộ lọc tồn kho
        if ($filters && is_array($filters)) {
            foreach ($filters as $filter) {
                switch ($filter) {
                    case 'duoi50': // Dưới định mức tồn (SL < 50)
                        $query->whereHas('variants.inventoryStock', function ($q) {
                            $q->select('variant_id')
                                ->selectRaw('SUM(quantity) as total_quantity')
                                ->groupBy('variant_id')
                                ->havingRaw('SUM(quantity) < ?', [50]);
                        });
                        break;

                    case 'tren500': // Vượt định mức tồn (SL > 500)
                        $query->whereHas('variants.inventoryStock', function ($q) {
                            $q->select('variant_id')
                                ->selectRaw('SUM(quantity) as total_quantity')
                                ->groupBy('variant_id')
                                ->havingRaw('SUM(quantity) > ?', [500]);
                        });
                        break;
                    case 'conhang': // Còn hàng trong kho (SL > 0)
                        $query->whereHas('variants.inventoryStock', function ($q) {
                            $q->select('variant_id')
                                ->selectRaw('SUM(quantity) as total_quantity')
                                ->groupBy('variant_id')
                                ->havingRaw('SUM(quantity) > ?', [0]);
                        });
                        break;
                    case 'tonkho0': // Hết hàng trong kho (SL = 0)
                        $query->whereHas('variants.inventoryStock', function ($q) {
                            $q->select('variant_id')
                                ->selectRaw('SUM(quantity) as total_quantity')
                                ->groupBy('variant_id')
                                ->havingRaw('SUM(quantity) = ?', [0]);
                        });
                        break;
                }
            }
        }
        $products = $query->get();

        return response()->json(ProductResource::collection($products));
    }
}
