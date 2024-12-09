<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\OrderItem;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public $totalAmount = 0;
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        try {
            $data = Cart::query()
                ->with(['variant.product.category', 'variant.attributeValues.attribute', 'variant.inventoryStock'])
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();


            foreach ($data as $item) {

                $this->totalAmount += $item['variant']['sale_price'] * $item['quantity'];

                $images = $item['variant']['product']['images'];
                if (is_string($images)) {
                    $item['variant']['product']['images'] = json_decode($images, true);
                }

                Log::info($item->variant->product->is_active);
                if ($item->variant->product->category->is_active === false || $item->variant->product->is_active === false) {
                    Cart::query()->where('variant_id', $item->variant_id)->delete();
                }
            }


            return response()->json([
                'status' => 'success',
                'data' => $data,
                'totalPrice' => $this->totalAmount
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Lỗi tải trang',
                'status' => 'error',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }




    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $data = $request->all();
            $data['user_id'] = Auth::id();
            $idExist = Cart::query()
                ->with(['variant.product.category', 'variant.attributeValues.attribute', "user", 'variant.inventoryStock'])
                ->where('variant_id', $request->variant_id)
                ->where('user_id', Auth::id())
                ->first();


            $variant = Variant::query()->with(['product.category', 'inventoryStock'])->find($request->variant_id);

            if ($variant->product->category->is_active === false || $variant->product->is_active === false) {
                return response()->json([
                    'message' => 'Sản phẩm không tồn tại'
                ], Response::HTTP_BAD_REQUEST);
            }


            if ($request->quantity > $variant->inventoryStock->quantity) {

                return response()->json([
                    'message' => 'Số lượng trong kho không đủ. Vui lòng giảm số lượng!'
                ], Response::HTTP_BAD_REQUEST);

            } else {
                if ($idExist) {
                    if ($idExist->where(''))
                        if ($request->quantity > 1) {
                            if ($idExist->quantity > $variant->inventoryStock->quantity) {
                                return response()->json([
                                    'message' => 'Số lượng trong kho không đủ!'
                                ], Response::HTTP_BAD_REQUEST);

                            }
                            $idExist->quantity += $request->quantity;
                            $idExist->save();
                        } else {
                            $idExist->quantity++;
                            $idExist->save();

                        }


                } else {
                    $idExist = Cart::query()->create($data);
                }
            }



            $idExist->load(['variant.product.category', 'variant.attributeValues.attribute', "user", 'variant.inventoryStock']);

            $images = $idExist->variant->product->images;
            if (is_string($images)) {
                $idExist->variant->product->images = json_decode($images, true);
            }

            return response()->json([
                'message' => 'Đã thêm sản phẩm vào giỏ hàng',
                'status' => 'success',
                'data' => $idExist,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $th) {

            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);

            return response()->json([
                'message' => 'Đã xảy ra lỗi vui lòng thử lại',
                'status' => 'error',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function update(Request $request, $id, $operation = null)
    {
        try {

            $idExist = Cart::query()
                ->with(['variant.attributeValues.attribute', "user", 'variant.inventoryStock'])
                ->find($id);


            if ($idExist) {

                if ($request->quantity >= 1) {
                    if ($request->quantity > $idExist->variant->inventoryStock->quantity) {
                        return response()->json([
                            'message' => 'Số lượng trong kho không đủ!'
                        ], Response::HTTP_BAD_REQUEST);

                    }
                    $idExist->quantity = $request->quantity;
                    $idExist->save();
                } else {
                    if ($operation) {

                        if ($operation === 'increase') {

                            $idExist->quantity += 1; // Tăng số lượng
                            if ($idExist->quantity > $idExist->variant->inventoryStock->quantity) {
                                $idExist->quantity = $idExist->variant->inventoryStock->quantity;
                                return response()->json([
                                    'message' => 'Số lượng trong kho không đủ!'
                                ], Response::HTTP_BAD_REQUEST);

                            }

                        } elseif ($operation === 'decrease') {

                            if ($idExist->quantity >= 1) {
                                $idExist->quantity -= 1; // Giảm số lượng nếu lớn hơn 1
                            }
                            if ($idExist->quantity === 0) {
                                Cart::where('id', $id)->delete();
                            }
                        }
                        $idExist->save();
                    }
                }



            }

            $data = Cart::query()
                ->with(['variant.product.category', 'variant.attributeValues.attribute'])
                ->where('user_id', Auth::id())
                ->get();

            foreach ($data as $item) {
                $this->totalAmount += $item['variant']['price'] * $item['quantity'];

            }


            return response()->json([
                // 'dataCart' => $data,
                'status' => 'success',
                'tutalPrice' => $this->totalAmount,
                'idExist' => $idExist

            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
            return response()->json([
                'messages' => 'Xảy ra lỗi. Vui lòng thử lại',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $userId = Auth::id(); // Lấy ID của người dùng đang đăng nhập
            $cartItem = Cart::where('id', $id)->where('user_id', $userId)->first();

            if (!$cartItem) {
                return response()->json(['message' => 'Mặt hàng trong giỏ hàng không được tìm thấy'], 404);
            }

            $cartItem->delete();

            return response()->json([
                'status' => 'success',
                'data' => $cartItem
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
            return response()->json([
                'messages' => 'Lỗi',
                'status' => 'error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //xóa nhiều cart
    public function deleteMuch(Request $request)
    {
        try {
            $ids = $request->ids;

            if (!is_array($ids) || empty($ids)) {
                return response()->json(['message' => 'Danh sách ID không hợp lệ!'], 400);
            }

            // Xóa nhiều theo id
            Cart::whereIn('id', $ids)->delete();

            return response()->json(['message' => 'Xóa nhiều giỏ hàng thành công!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    public function addCartMultil(Request $request, $id_user)
    {
        try {
            // Kiểm tra nếu không có id_user
            if (!$id_user) {
                return response()->json([
                    'message' => 'id_user không được cung cấp',
                    'status' => 'error',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Lấy danh sách local cart từ request
            $localCart = $request->input('local_cart', []);

            // Kiểm tra dữ liệu local_cart
            if (empty($localCart) || !is_array($localCart)) {
                return response()->json([
                    'message' => 'Dữ liệu local_cart không hợp lệ',
                    'status' => 'error',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Duyệt qua từng item trong local_cart
            foreach ($localCart as $item) {
                $variantId = $item['variant_id'] ?? null;
                $quantity = $item['quantity'] ?? 0;

                if (!$variantId || $quantity <= 0) {
                    return response()->json([
                        'message' => 'Dữ liệu không hợp lệ trong local_cart',
                        'status' => 'error',
                    ], Response::HTTP_BAD_REQUEST);
                }

                // Kiểm tra xem variant đã tồn tại trong giỏ hàng của user chưa
                $existingCart = Cart::query()
                    ->where('variant_id', $variantId)
                    ->where('user_id', $id_user)
                    ->first();

                if (!$existingCart) {
                    // Thêm mới sản phẩm vào giỏ hàng
                    Cart::create([
                        'user_id' => $id_user,
                        'variant_id' => $variantId,
                        'quantity' => $quantity,
                    ]);
                }
                
            }

            // Lấy lại danh sách giỏ hàng sau khi cập nhật
            $updatedCart = Cart::query()
                ->with(['variant.product.category', 'variant.attributeValues.attribute', 'variant.inventoryStock'])
                ->where('user_id', $id_user)
                ->get();

            return response()->json([
                'message' => 'Giỏ hàng đã được cập nhật thành công',
                'status' => 'success',
                'data' => $updatedCart,
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error in addCartMultil method: ', [
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
            ]);

            return response()->json([
                'message' => 'Đã xảy ra lỗi vui lòng thử lại',
                'status' => 'error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getVariant($id_variant)
    {
        try {
            $variant = Variant::with([
                'product',
                'product.category',
                'attributeValues.attribute',
                'inventoryStock'
            ])->find($id_variant);

            if (!$variant) {
                return response()->json([
                    'message' => 'Variant not found'
                ], 404);
            }

            // Giải mã trường images nếu là chuỗi JSON
            if (is_string($variant->product->images)) {
                $variant->product->images = json_decode($variant->product->images, true);
            }

            return response()->json($variant, 200);
        } catch (\Exception $e) {
            // Ghi lại lỗi trong log để kiểm tra sau
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $e->getLine(),
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'An error occurred while fetching the variant',
                'status' => 'error'
            ], 500);
        }
    }


}