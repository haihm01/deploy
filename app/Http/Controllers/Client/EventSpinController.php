<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Event;
use App\Models\EventSpin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventSpinController extends Controller
{
    public function spinEvent(Request $request)
    {
        try {
            $user = $request->user();

            // Kiểm tra nếu coupon_id được truyền lên từ phía frontend
            $couponId = $request->coupon_id;

            // Kiểm tra sự tồn tại của coupon
            $coupon = Coupon::find($couponId);

            if (!$coupon || $coupon->type != 'event') {
                return response()->json(['message' => 'Coupon không hợp lệ hoặc không phải là event.'], 404);
            }

            // Kiểm tra nếu coupon đã hết số lượng
            if ($coupon->usage_limit <= 0) {
                return response()->json(['message' => 'Coupon đã hết số lượng.'], 404);
            }

            // Lưu lịch sử quay
            $eventSpin = EventSpin::create([
                'event_id' => $request->event_id, // event_id từ frontend
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'status' => 'success',
            ]);

            // Lưu coupon vào bảng `coupon_users`
            CouponUser::create([
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
            ]);

            // Giảm số lượng coupon
            $coupon->decrement('usage_limit');

            return response()->json([
                'message' => 'Chúc mừng! Bạn đã nhận được coupon.',
                'spin' => $eventSpin,
                'coupon' => $coupon,
            ], 201);
        } catch (\Exception $e) {
            // Nếu có lỗi xảy ra, trả về lỗi với thông tin chi tiết
            return response()->json([
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getSpinHistoryByEvent(Request $request)
{
    try {
        // Lấy thông tin sự kiện từ request
        $eventId = $request->event_id;

        // Kiểm tra nếu event_id được cung cấp
        if (!$eventId) {
            return response()->json([
                'message' => 'Vui lòng cung cấp event_id.'
            ], 400); // 400 là mã lỗi yêu cầu không hợp lệ
        }

        // Truy vấn lịch sử quay của sự kiện
        $spinHistory = EventSpin::with('user', 'coupon')
            ->where('event_id', $eventId) // Lọc theo event_id
            ->get();

        // Kiểm tra nếu không có lịch sử quay
        if ($spinHistory->isEmpty()) {
            return response()->json([
                'message' => 'Không có lịch sử quay cho sự kiện này.'
            ], 404); // 404 là mã lỗi không tìm thấy
        }

        // Trả về kết quả
        return response()->json([
            'message' => 'Danh sách lịch sử quay.',
            'spin_history' => $spinHistory
        ]);
    } catch (\Exception $e) {
        // Nếu có lỗi xảy ra
        return response()->json([
            'message' => 'Đã có lỗi xảy ra khi lấy lịch sử quay.',
            'error' => $e->getMessage()
        ], 500); // 500 là mã lỗi server
    }
}

public function getUserSpinHistoryByEvent(Request $request)
{
    try {
        $user = $request->user(); // Lấy thông tin người dùng hiện tại

        // Lấy thông tin sự kiện từ request
        $eventId = $request->event_id;

        // Kiểm tra nếu event_id được cung cấp
        if (!$eventId) {
            return response()->json([
                'message' => 'Vui lòng cung cấp event_id.'
            ], 400); // 400 là mã lỗi yêu cầu không hợp lệ
        }

        // Truy vấn lịch sử quay của người dùng theo sự kiện
        $spinHistory = EventSpin::with('coupon') 
            ->where('event_id', $eventId) // Lọc theo event_id
            ->where('user_id', $user->id) // Lọc theo user_id
            ->get();

        // Kiểm tra nếu không có lịch sử quay
        if ($spinHistory->isEmpty()) {
            return response()->json([
                'message' => 'Không có lịch sử quay cho người dùng này trong sự kiện này.'
            ], 404); // 404 là mã lỗi không tìm thấy
        }

        // Trả về kết quả
        return response()->json([
            'message' => 'Danh sách lịch sử quay của người dùng theo sự kiện.',
            'spin_history' => $spinHistory
        ]);
    } catch (\Exception $e) {
        // Nếu có lỗi xảy ra
        return response()->json([
            'message' => 'Đã có lỗi xảy ra khi lấy lịch sử quay.',
            'error' => $e->getMessage()
        ], 500); // 500 là mã lỗi server
    }
}


}
