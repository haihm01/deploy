<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InventoryImportRequest;
use App\Http\Requests\Inventory\UpdateInventoryImportRequest;
use App\Http\Resources\ProductResource;
use App\Models\InventoryImport;
use App\Models\InventoryImportHistory;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryImportController extends Controller
{

    // public function index()
    // {
    //     $imports = InventoryImport::with(['variant.product.category', 'supplier'])
    //         ->orderByDesc('id')
    //         ->paginate(10);

    //     // Map lại dữ liệu để decode
    //     $mappedImports = $imports->getCollection()->map(function ($import) {
    //         return [
    //             'id' => $import->id,
    //             'quantity' => $import->quantity,
    //             'import_price' => $import->import_price,
    //             'variant_id' => $import->variant_id,
    //             'supplier_id' => $import->supplier_id,
    //             'created_at' => $import->created_at,
    //             'updated_at' => $import->updated_at,
    //             'variant' => $import->variant ? [
    //                 'id' => $import->variant->id,
    //                 'price' => $import->variant->price,
    //                 'sale_price' => $import->variant->sale_price,
    //                 'end_sale' => $import->variant->end_sale,
    //                 'image' => $import->variant->image,
    //                 'product_id' => $import->variant->product_id,
    //                 'created_at' => $import->variant->created_at,
    //                 'updated_at' => $import->variant->updated_at,
    //                 'product' => $import->variant->product ? [
    //                     'id' => $import->variant->product->id,
    //                     'name' => $import->variant->product->name,
    //                     'slug' => $import->variant->product->slug,
    //                     'images' => json_decode($import->variant->product->images, true),
    //                     'description' => $import->variant->product->description,
    //                     'category_id' => $import->variant->product->category_id,
    //                     'is_featured' => $import->variant->product->is_featured,
    //                     'is_active' => $import->variant->product->is_active,
    //                     'deleted_at' => $import->variant->product->deleted_at,
    //                     'created_at' => $import->variant->product->created_at,
    //                     'updated_at' => $import->variant->product->updated_at,
    //                     'category' => $import->variant->product->category ? [
    //                         'id' => $import->variant->product->category->id,
    //                         'name' => $import->variant->product->category->name,
    //                         'slug' => $import->variant->product->category->slug,
    //                         'image' => $import->variant->product->category->image,
    //                     ] : null,
    //                 ] : null,
    //             ] : null,
    //             'supplier' => $import->supplier ? [
    //                 'id' => $import->supplier->id,
    //                 'name' => $import->supplier->name,
    //                 'phone' => $import->supplier->phone,
    //                 'email' => $import->supplier->email,
    //                 'address' => $import->supplier->address,
    //                 'created_at' => $import->supplier->created_at,
    //                 'updated_at' => $import->supplier->updated_at,
    //             ] : null,
    //         ];
    //     });

    //     $imports->setCollection($mappedImports);

    //     return response()->json([
    //         'message' => 'Danh sách nhập hàng',
    //         'data' => $imports,
    //     ], 200);
    // }
    public function index()
    {
        $products = Product::with(['category', 'variants.attributeValues.attribute', 'variants.inventoryStock', 'variants.inventoryImports.supplier'])->paginate(10);

        return ProductResource::collection($products);
    }
    public function productsWithInventoryImports()
    {
        $products = Product::whereHas('variants.inventoryImports')
            ->with([
                'category',
                'variants.attributeValues.attribute',
                'variants.inventoryStock',
                'variants.inventoryImports.supplier'
            ])
            ->paginate(10);

        return ProductResource::collection($products);
    }
    public function getAllProductNoImport()
    {
        $products = Product::with([
            'category',
            'variants.attributeValues.attribute',
            'variants.inventoryStock',
            'variants.inventoryImports.supplier',
        ])
            ->whereHas('variants', function ($query) {
                $query->doesntHave('inventoryImports');
            })
            ->paginate(10);

        return ProductResource::collection($products);
    }


    public function import(InventoryImportRequest $request)
    {
        try {
            DB::beginTransaction();

            // Lấy thông tin variant duy nhất từ request
            $variantData = $request->variants[0]; // Chỉ xử lý một variant duy nhất
            $batchNumber = Str::uuid();  // Hoặc sử dụng một cách tạo mã khác

            // Tạo một bản ghi mới trong InventoryImport
            $import = InventoryImport::create([
                'variant_id' => $variantData['variant_id'],
                'supplier_id' => $variantData['supplier_id'],
                'quantity' => $variantData['quantity'],
                'import_price' => $variantData['import_price'],
                'batch_number' => $batchNumber,  // Lưu mã lô nhập

            ]);

            // Cập nhật hoặc tạo mới stock
            $stock = InventoryStock::firstOrNew([
                'variant_id' => $variantData['variant_id']
            ]);
            $stock->quantity = ($stock->quantity ?? 0) + $variantData['quantity'];
            $stock->save();

            // Lưu lịch sử nhập hàng
            InventoryImportHistory::create([
                'variant_id' => $variantData['variant_id'],
                'supplier_id' => $variantData['supplier_id'],
                'quantity' => $variantData['quantity'],
                'import_price' => $variantData['import_price'],
                'batch_number' => $batchNumber,  // Lưu mã lô nhập vào lịch sử


            ]);

            DB::commit();

            return response()->json([
                'message' => 'Nhập hàng thành công!',
                'import' => $import,
                'stock' => $stock,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Có lỗi xảy ra.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function importMultiple(InventoryImportRequest $request)
    {
        $validatedData = $request->validated();

        try {
            DB::beginTransaction();

            $updateResults = [];
            $historyData = []; // Mảng để lưu lịch sử

            foreach ($validatedData['variants'] as $variantData) {
                // Kiểm tra và lấy thông tin variant
                $variant = Variant::find($variantData['variant_id']);
                $batchNumber = Str::uuid();
                // 1. Tạo bản ghi mới trong InventoryImport
                $import = InventoryImport::create([
                    'variant_id' => $variantData['variant_id'],
                    'supplier_id' => $variantData['supplier_id'],
                    'quantity' => $variantData['quantity'],
                    'import_price' => $variantData['import_price'],
                    'batch_number' => $batchNumber,  // Lưu mã lô nhập

                ]);

                // 2. Cập nhật hoặc tạo mới stock
                $stock = InventoryStock::firstOrNew([
                    'variant_id' => $variantData['variant_id']
                ]);

                $stock->quantity = ($stock->quantity ?? 0) + $variantData['quantity'];
                $stock->save();

                // 3. Thêm lịch sử nhập hàng
                $historyData[] = [
                    'variant_id' => $variant->id,
                    'supplier_id' => $variantData['supplier_id'],
                    'quantity' => $variantData['quantity'],
                    'import_price' => $variantData['import_price'],
                    'batch_number' => $batchNumber,  // Lưu mã lô nhập vào lịch sử
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // 4. Lưu kết quả
                $updateResults[] = [
                    'updated_stock' => $stock,
                    'new_import' => $import
                ];
            }

            // Lưu lịch sử vào bảng `inventory_import_history`
            InventoryImportHistory::insert($historyData);

            DB::commit();

            return response()->json([
                'message' => 'Nhập hàng nhiều thành công!',
                'results' => $updateResults
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Có lỗi xảy ra.',
                'message' => $e->getMessage()
            ], 500);
        }
    }





    public function updateVariantPrices(Request $request, $variantId)
    {
        $validatedData = $request->validate([
            'price' => 'required|integer|min:0',
            'sale_price' => 'nullable|integer|min:0|lt:price',
            'end_sale' => 'nullable|date|after_or_equal:now',
        ], [
            'price.required' => 'Giá sản phẩm là bắt buộc.',
            'price.integer' => 'Giá sản phẩm phải là số nguyên.',
            'price.min' => 'Giá sản phẩm phải lớn hơn hoặc bằng 0.',
            'sale_price.integer' => 'Giá khuyến mãi phải là số nguyên.',
            'sale_price.min' => 'Giá khuyến mãi phải lớn hơn hoặc bằng 0.',
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá bán.',
            'end_sale.date' => 'Ngày kết thúc khuyến mãi không hợp lệ.',
            'end_sale.after_or_equal' => 'Ngày kết thúc khuyến mãi phải từ hôm nay trở đi.',
        ]);

        try {
            $variant = Variant::findOrFail($variantId);

            $variant->update([
                'price' => $validatedData['price'],
                'sale_price' => $validatedData['sale_price'] ?? null,
                'end_sale' => $validatedData['end_sale'] ?? null,
            ]);

            return response()->json([
                'message' => 'Cập nhật giá sản phẩm thành công!',
                'variant' => $variant,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Cập nhật không thành công.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // cập nhập 1 lần

    public function updateVariant(UpdateInventoryImportRequest $request)
    {
        try {
            DB::beginTransaction();

            $variantData = $request->variants[0];



            // 1. Tìm và cập nhật bản ghi nhập hàng (InventoryImport)
            $import = InventoryImport::where('variant_id', $variantData['variant_id'])->first();

            // Cập nhật thông tin nhập
            $import->supplier_id = $variantData['supplier_id'];
            $import->import_price = $variantData['import_price']; // Cập nhật giá nhập


            $import->save();



            DB::commit();

            return response()->json([
                'message' => 'Nhập hàng thành công!',
                'import' => $import,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Có lỗi xảy ra.',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    // cập nhập nhiều lần

    public function UpdateMultiple(UpdateInventoryImportRequest $request)
    {
        $validatedData = $request->validated();

        try {
            DB::beginTransaction();

            $updateResults = [];

            foreach ($validatedData['variants'] as $variantData) {
                // Kiểm tra và lấy thông tin variant
                $variant = Variant::find($variantData['variant_id']);

                if (!$variant) {
                    throw new \Exception("Biến thể với ID {$variantData['variant_id']} không tồn tại.");
                }

                // 1. Cập nhật thông tin variant
                if (isset($variantData['price'])) {
                    $variant->price = $variantData['price'];
                }

                if (isset($variantData['sale_price'])) {
                    $variant->sale_price = $variantData['sale_price'];
                }

                if (isset($variantData['end_sale'])) {
                    $variant->end_sale = $variantData['end_sale'];
                }

                $variant->save();



                // 3. Cập nhật giá nhập nếu cần
                $import = InventoryImport::firstOrNew([
                    'variant_id' => $variantData['variant_id']
                ]);

                $import->fill([
                    'import_price' => $variantData['import_price'],
                    'supplier_id' => $variantData['supplier_id']
                ])->save();

                // 4. Lưu kết quả
                $updateResults[] = [
                    'updated_variant' => $variant,
                    'updated_import' => $import
                ];
            }

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật thành công!',
                'results' => $updateResults
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Có lỗi xảy ra.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function storeInventoryHistory($inventoryImportId)
    {
        $inventoryImport = InventoryImport::findOrFail($inventoryImportId);

        $variant = Variant::findOrFail($inventoryImport->variant_id);

        $supplier = Supplier::findOrFail($inventoryImport->supplier_id);

        InventoryImportHistory::create([
            'variant_id' => $variant->id,
            'supplier_id' => $supplier->id,
            'quantity' => $inventoryImport->quantity,
            'import_price' => $inventoryImport->import_price,
            'price' => $variant->price,
            'sale_price' => $variant->sale_price,
            'end_sale' => $variant->end_sale,

        ]);
    }

    public function getInventoryHistory()
    {

        $products = Product::with([
            'category',
            'variants.attributeValues.attribute',
            'variants.inventoryStock',
            'variants.inventoryImports.supplier',
        ])
            ->whereHas('variants', function ($query) {
                $query->doesntHave('inventoryImports');
            })
            ->paginate(10);

        return ProductResource::collection($products);
    }


    public function checkAvailableStock()
    {
        try {
            $products = InventoryImportHistory::with([
                'variant.attributeValues.attribute',
                'variant.product',
                'supplier'

            ])->paginate(10);

            $result = [];

            foreach ($products as $product) {
                $import = InventoryImport::where('batch_number', $product->batch_number)->first();

                if ($import) {
                    $quantityAvailable = $import->quantity;

                    $productStatus = $quantityAvailable > 0 ? 'Còn hàng' : 'Hết hàng';

                    if ($product->status !== $productStatus) {
                        $product->update(['status' => $productStatus]);
                    }
                } else {
                    $quantityAvailable = 0;
                    $productStatus = 'Hết hàng';

                    if ($product->status !== $productStatus) {
                        $product->update(['status' => $productStatus]);
                    }
                }

                $result[] = [
                    'id' => $product->id,
                    'product' => [
                            'name' => $product->variant->product->name ?? 'N/A',
                            'images' => json_decode($product->variant->product->images, true) ?? []

                    ],
                    'quantity_import_history' => $product->quantity,
                    'quantity_available' => $quantityAvailable,
                    'import_price' => $product->import_price,
                    'batch_number' => $product->batch_number,
                    'status' => $productStatus,
                    'variant' => $product->variant,
                    'supplier' => $product->supplier,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ];
            }

            return response()->json([
                'message' => 'Danh sách các sản phẩm và trạng thái đã được cập nhật.',
                'data' => $result,
                'pagination' => [
                    'total' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function restoreImport($id)
    {
        try {
            // Tìm bản ghi đã bị xóa mềm
            $inventoryImport = InventoryImport::onlyTrashed()->find($id);

            if (!$inventoryImport) {
                return response()->json([
                    'message' => 'Không tìm thấy bản ghi đã bị xóa với ID: ' . $id
                ], 404);
            }

            // Tìm InventoryStock liên quan đến variant_id
            $inventoryStock = InventoryStock::where('variant_id', $inventoryImport->variant_id)->first();

            if ($inventoryStock) {
                // Cộng lại số lượng vào InventoryStock
                $inventoryStock->quantity += $inventoryImport->quantity;
                $inventoryStock->save();
            } else {
                // Nếu không tồn tại bản ghi InventoryStock, có thể tạo mới (tùy logic của bạn)
                $inventoryStock = InventoryStock::create([
                    'variant_id' => $inventoryImport->variant_id,
                    'quantity' => $inventoryImport->quantity
                ]);
            }

            // Khôi phục bản ghi đã bị xóa mềm
            $inventoryImport->restore();

            return response()->json([
                'message' => 'Đã khôi phục bản ghi thành công và cập nhật số lượng trong kho.',
                'data' => [
                    'inventory_import' => $inventoryImport,
                    'updated_inventory_stock' => $inventoryStock
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra khi khôi phục bản ghi.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function deleteImport($id)
    {
        try {
            $inventoryImport = InventoryImport::with(['supplier'])->find($id);

            if (!$inventoryImport) {
                return response()->json([
                    'message' => 'Không tìm thấy bản ghi với ID: ' . $id
                ], 404);
            }

            $inventoryStock = InventoryStock::where('variant_id', $inventoryImport->variant_id)->first();

            if ($inventoryStock) {
                $newQuantity = $inventoryStock->quantity - $inventoryImport->quantity;

                $inventoryStock->quantity = max($newQuantity, 0);
                $inventoryStock->save();
            }

            $inventoryImport->delete(); // Xóa mềm bản ghi

            return response()->json([
                'message' => 'Đã xóa mềm bản ghi thành công và cập nhật số lượng trong kho.',
                'data' => [
                    'inventory_import' => $inventoryImport,
                    'updated_inventory_stock' => $inventoryStock
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra khi xóa bản ghi.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    // public function deleteImport($id)
    // {
    //     try {
    //         $inventoryImport = InventoryImport::with(['supplier'])->find($id);

    //         if (!$inventoryImport) {
    //             return response()->json([
    //                 'message' => 'Không tìm thấy bản ghi với ID: ' . $id
    //             ], 404);
    //         }

    //         $inventoryStock = InventoryStock::where('variant_id', $inventoryImport->variant_id)->first();

    //         if ($inventoryStock) {
    //             $newQuantity = $inventoryStock->quantity - $inventoryImport->quantity;

    //             $inventoryStock->quantity = max($newQuantity, 0);
    //             $inventoryStock->save();
    //         }

    //         $inventoryImport->delete();

    //         return response()->json([
    //             'message' => 'Đã xóa bản ghi thành công và cập nhật số lượng trong kho.',
    //             'data' => [
    //                 'inventory_import' => $inventoryImport,
    //                 'updated_inventory_stock' => $inventoryStock
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Có lỗi xảy ra khi xóa bản ghi.',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getByVariantId($variantId)
    {
        try {
            // Lấy các bản ghi từ InventoryImport dựa trên variant_id
            $records = InventoryImport::with(['supplier'])
                ->where('variant_id', $variantId)
                ->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'message' => 'Không tìm thấy bản ghi nào liên quan đến variant_id: ' . $variantId,
                ], 404);
            }

            $result = [];

            foreach ($records as $record) {
                // Lấy thông tin từ InventoryImportHistory dựa trên batch_number
                $history = InventoryImportHistory::where('batch_number', $record->batch_number)->first();

                $result[] = [
                    'inventory_import' => $record,
                    'quantity_imported' => $history->quantity ?? 0, // Số lượng nhập đã lưu
                    'batch_number' => $record->batch_number,
                ];
            }

            return response()->json([
                'message' => 'Danh sách các bản ghi liên quan đến variant_id: ' . $variantId,
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra.',
                'message' => $e->getMessage()
            ], 500);
        }
    }



public function deleteHistoryRecord($id)
{
    try {
        $history = InventoryImportHistory::find($id);

        if (!$history) {
            return response()->json([
                'message' => 'Không tìm thấy bản ghi với ID: ' . $id
            ], 404);
        }

        if ($history->status !== 'Hết hàng') {
            return response()->json([
                'message' => 'Bản ghi không thể xóa vì trạng thái không phải là "Hết hàng".'
            ], 400);
        }

        $history->delete();

        return response()->json([
            'message' => 'Bản ghi đã được xóa thành công.',
            'deleted_record' => $history
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Có lỗi xảy ra khi xóa bản ghi.',
            'message' => $e->getMessage()
        ], 500);
    }
}



public function checkDateHistore(Request $request)
{
    try {
        // Nhận tham số từ request
        $fromDate = $request->input('from_date'); // Ngày bắt đầu
        $toDate = $request->input('to_date');    // Ngày kết thúc

        // Lấy query ban đầu
        $query = InventoryImportHistory::with([
            'variant.attributeValues.attribute',
            'variant.product',
            'supplier'
        ]);

        // Áp dụng bộ lọc ngày nếu có
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // Phân trang
        $products = $query->paginate(10);

        $result = [];

        foreach ($products as $product) {
            $import = InventoryImport::where('batch_number', $product->batch_number)->first();

            if ($import) {
                $quantityAvailable = $import->quantity;

                $productStatus = $quantityAvailable > 0 ? 'Còn hàng' : 'Hết hàng';

                if ($product->status !== $productStatus) {
                    $product->update(['status' => $productStatus]);
                }
            } else {
                $quantityAvailable = 0;
                $productStatus = 'Hết hàng';

                if ($product->status !== $productStatus) {
                    $product->update(['status' => $productStatus]);
                }
            }

            $result[] = [
                'quantity_import_history' => $product->quantity,
                'quantity_available' => $quantityAvailable,
                'import_price' => $product->import_price,
                'batch_number' => $product->batch_number,
                'status' => $productStatus,
                'variant' => $product->variant,
                'supplier' => $product->supplier,
                'images' => $product->variant->product->images ?? [],
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at
            ];
        }

        return response()->json([
            'message' => 'Danh sách các sản phẩm và trạng thái đã được cập nhật.',
            'data' => $result,
            'pagination' => [
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem()
            ]
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Có lỗi xảy ra.',
            'message' => $e->getMessage()
        ], 500);
    }
}
public function getDetailImport(string $id)
    {
        $product = Product::with('category','variants.attributeValues.attribute', 'variants.inventoryStock','variants.inventoryImports.supplier'
        )->findOrFail($id);
        return new ProductResource($product);
    }
}
