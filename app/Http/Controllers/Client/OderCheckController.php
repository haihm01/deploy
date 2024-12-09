<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CouponUser;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OderCheckController extends Controller
{
    public function checkOrder(Request $request)
{
    $request->validate([
        'search' => 'required|string',
    ]);

    try {
        $search = trim($request->input('search'));

        if (preg_match('/[A-Za-z]/', $search)) {
            $order = Order::where('code', $search)
                ->with(['items.variant.product', 'items.variant.attributeValues'])
                ->first();

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy đơn hàng với mã đã cung cấp',
                ], 404);
            }

            foreach ($order->items as $item) {
                if (is_string($item->variant->product->images)) {
                    $item->variant->product->images = json_decode($item->variant->product->images, true);
                }
            }

            $order->name = $this->maskName($order->name);
            $order->tel = $this->maskTel($order->tel);
            $order->address = $this->maskAddress($order->address);

            return response()->json([
                'status' => 'success',
                'orders' => [$order],
            ]);
        }

        $orders = Order::where('tel', $search)
            ->with(['items.variant.product', 'items.variant.attributeValues'])
            ->orderByDesc('created_at')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy đơn hàng với số điện thoại đã cung cấp',
            ], 404);
        }

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (is_string($item->variant->product->images)) {
                    $item->variant->product->images = json_decode($item->variant->product->images, true);
                }
            }
            $order->name = $this->maskName($order->name);
            $order->tel = $this->maskTel($order->tel);
            $order->address = $this->maskAddress($order->address);
        }

        return response()->json([
            'status' => 'success',
            'orders' => $orders,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Có lỗi xảy ra khi kiểm tra đơn hàng',
        ], 500);
    }
}


    private function maskName($name)
    {
        if (empty($name)) return '';

        $length = mb_strlen($name);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . mb_substr($name, -4);
    }

    private function maskTel($tel)
    {
        if (empty($tel)) return '';

        $length = strlen($tel);
        if ($length <= 3) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 3) . substr($tel, -3);
    }

    private function maskAddress($address)
    {
       if (empty($address)) return '';

       $length = mb_strlen($address);
       if ($length <= 15) {
           return str_repeat('*', $length);
       }

       return str_repeat('*', $length - 15) . mb_substr($address, -15);
    }



    public function getEventCoupons(Request $request)
    {
        // Lấy thông tin người dùng
        $user = $request->user();

        $eventCoupons = CouponUser::where('user_id', $user->id)
            ->whereNull('used_at') 
            ->join('coupons', 'coupon_users.coupon_id', '=', 'coupons.id')
            ->where('coupons.type', 'event')
            ->select('coupon_users.coupon_id', 'coupons.*', \DB::raw('COUNT(coupon_users.coupon_id) as total_count')) // Đếm số lượng coupon
            ->groupBy('coupon_users.coupon_id')
            ->get();

        return response()->json([
            'event_coupons' => $eventCoupons,
        ]);
    }





}
