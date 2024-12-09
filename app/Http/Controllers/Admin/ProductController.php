<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index()
{
    $products = Product::with(['category', 'variants.attributeValues.attribute', 'variants.inventoryStock'])
    ->orderBy('created_at', 'desc')
    ->paginate(15);

        return ProductResource::collection($products);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        try {
            // Tạo Product
            $product = Product::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'images' => json_encode($request->images),
                'category_id' => $request->category_id,
                'is_featured' => $request->is_featured ?? false,
                'is_active' => $request->is_active ?? true,
            ]);

            foreach ($request->variants as $variantData) {
                $variant = Variant::create([
                    'price' => $variantData['price'],
                    'sale_price' => $variantData['sale_price'],
                    'end_sale' => $variantData['end_sale'] ?? null,
                    'image' => $variantData['image'] ?? null,
                    'product_id' => $product->id,
                ]);

                if (isset($variantData['quantity'])) {
                    InventoryStock::create([
                        'variant_id' => $variant->id,
                        'quantity' => $variantData['quantity'],
                    ]);
                }

                if (isset($variantData['attribute_values'])) {
                    foreach ($variantData['attribute_values'] as $attributeValueId) {
                        $variant->attributeValues()->attach($attributeValueId);
                    }
                }
            }

            return new ProductResource($product->load('category', 'variants.attributeValues.attribute', 'variants.inventoryStock', ));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Thêm Product thất bại', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with('category', 'variants.attributeValues.attribute', 'variants.inventoryStock')->findOrFail($id);
        return new ProductResource($product);
    }


    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->update([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'images' => json_encode($request->images),
                'category_id' => $request->category_id,
                'is_featured' => $request->is_featured ?? false,
                'is_active' => $request->is_active ?? true,
            ]);

            $variantIds = [];

            foreach ($request->variants as $variantData) {
                $variant = Variant::updateOrCreate(
                    ['id' => $variantData['id'] ?? null, 'product_id' => $product->id],
                    [
                        'price' => $variantData['price'],
                        'sale_price' => $variantData['sale_price'],
                        'end_sale' => $variantData['end_sale'] ?? null,
                        'image' => $variantData['image'] ?? null,
                    ]
                );

                if (isset($variantData['attribute_values'])) {
                    $variant->attributeValues()->sync($variantData['attribute_values']);
                }

                $variantIds[] = $variant->id;
            }

            $product->variants()->whereNotIn('id', $variantIds)->delete();

            return response()->json(new ProductResource(
                $product->load(['category', 'variants.attributeValues.attribute', 'variants.inventoryStock'])
            ), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }






    public function findBySlug(string $slug): JsonResponse
    {
        try {
            $product = Product::with('category', 'variants.attributeValues.attribute', 'variants.inventoryStock')
                ->where('slug', $slug)
                ->firstOrFail();

            return response()->json(new ProductResource($product), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Product not found.'], 404);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::with('category')->findOrFail($id);
            $productData = $product->toArray();
            if (isset($productData['images'])) {
                $productData['images'] = json_decode($productData['images'], true); 
            }
            $product->delete();
            return response()->json([
                'message' => 'Product xóa thành công',
                'data' => $productData 
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Xóa product thất bại', 'error' => $e->getMessage()], 500);
        }
    }

    //updateIsFeatured
    public function updateIsFeatured(Request $request, string $id){
        $product = Product::with('category')->findOrFail($id);
        $product->update(['is_featured'=>$request->is_featured]);

        return response()->json([
            'message' => 'Cập nhật is_featured thành công!',
            'data' => new ProductResource($product),
        ], 200);
    }

    // //updateIsGoodDeal
    // public function updateIsGoodDeal(Request $request, string $id){
    //     $product = Product::findOrFail($id);
    //     $product->update(['is_good_deal'=>$request->is_good_deal]);

    //     return response()->json([
    //         'message' => 'Cập nhật is_good_deal thành công!',
    //         'data' => new ProductResource($product),
    //     ], 200);
    // }

    //updateIsActive
  
    public function updateIsActive(Request $request, string $id){
        $product = Product::with('category')->findOrFail($id);
        $product->update(['is_active'=>$request->is_active]);


        return response()->json([
            'message' => 'Cập nhật is_active thành công!',
            'data' => new ProductResource($product),
        ], 200);
    }

    //khôi phục product (chuyển deleted_at về null)
    public function restore(string $id)
    {
        $product = Product::withTrashed()->findOrFail($id);

        $product->restore();

        return response()->json(['message' => 'Khôi phục Sản Phẩm thành công!'], 200);
    }

    //xóa cứng
    public function hardDelete(string $id)
    {
        $category = Product::withTrashed()->findOrFail($id);

        $category->forceDelete();

        return response()->json(['message' => 'Xóa vĩnh viễn Sản Phẩm thành công!'], 200);
    }


    //khôi phục nhiều
    public function restoreMultiple(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['message' => 'Danh sách ID không hợp lệ'], 400);
        }

        $restoredCount = Product::withTrashed()
            ->whereIn('id', $ids)
            ->restore();

        return response()->json([
            'message' => 'Khôi phục nhiều sản phẩm thành công!',
            'so_luong' => $restoredCount
        ], 200);
    }
     public function listDelete()
    {
        $products = Product::onlyTrashed()
            ->with(['category', 'variants.attributeValues.attribute', 'variants.inventoryStock'])
            ->paginate(10);

        return ProductResource::collection($products);
    }

}
