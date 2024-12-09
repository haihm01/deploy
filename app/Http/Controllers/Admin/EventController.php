<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Coupon;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function getAllEventCoupons()
    {
        // Truy vấn các coupon có type là 'event'
        $eventCoupons = Coupon::where('type', 'event')->get();

        return response()->json([
            'event_coupons' => $eventCoupons,
        ]);
    }



    public function getEventCoupons()
    {
        // Lấy tất cả các sự kiện cùng với coupon có type là 'event'
        $events = Event::with(['coupons' => function ($query) {
            $query->where('type', 'event');
        }])->get();

        // Sử dụng EventResource để định dạng dữ liệu
        return EventResource::collection($events);
    }

    // Thêm mới một vòng quay
    public function createEvent(Request $request)
{
    try {
        // Xác thực dữ liệu đầu vào
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'required|boolean', // Thêm xác thực cho trường is_active
            'coupons' => 'array', // Mảng ID coupon
            'coupons.*' => 'exists:coupons,id' // Kiểm tra từng ID coupon
        ]);

        // Tạo sự kiện mới
        $event = Event::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active, // Thêm trường is_active
        ]);

        // Gán coupon cho sự kiện (nếu có)
        if ($request->has('coupons')) {
            $event->coupons()->attach($request->coupons);
            // Tải lại mối quan hệ coupons để lấy dữ liệu đầy đủ
            $event->load('coupons');
        }

        // Tính tổng xác suất thắng
        $totalWinningProbability = $event->coupons->sum('winning_probability');

        $couponsData = $event->coupons->map(function ($coupon) use ($totalWinningProbability) {
            // Tính tỷ lệ phần trăm
            $probabilityPercentage = ($totalWinningProbability > 0) ? ($coupon->winning_probability / $totalWinningProbability) * 100 : 0;

            return [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'code' => $coupon->code,
                'discount' => $coupon->discount,
                'discount_type' => $coupon->discount_type,
                'usage_limit' => $coupon->usage_limit,
                'min_order_value' => $coupon->min_order_value,
                'max_order_value' => $coupon->max_order_value,
                'status' => $coupon->status,
                'winning_probability' => $coupon->winning_probability,
                'probability_percentage' => round($probabilityPercentage, 2),
                'created_at' => $coupon->created_at,
                'updated_at' => $coupon->updated_at,
                'pivot' => [
                    'event_id' => $coupon->pivot->event_id,
                    'coupon_id' => $coupon->pivot->coupon_id,
                ],
            ];
        });

        return response()->json([
            'message' => 'Vòng quay đã được tạo thành công!',
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'is_active' => $event->is_active,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
                'coupons' => $couponsData,
            ]
        ], 201);

    } catch (\Exception $e) {
        // Bắt lỗi nếu có ngoại lệ xảy ra
        return response()->json([
            'message' => 'Đã xảy ra lỗi khi tạo sự kiện.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



    public function showEvent($id)
    {
        $eventWithCoupons = Event::with('coupons')->findOrFail($id);

        return new EventResource($eventWithCoupons);
    }





    public function updateEvent(Request $request, $id)
    {
        try {
            // Xác thực dữ liệu đầu vào
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_active' => 'required|boolean', // Thêm xác thực cho trường is_active
                'coupons' => 'array', // Mảng ID coupon
                'coupons.*' => 'exists:coupons,id', // Kiểm tra từng ID coupon
                'winning_probabilities' => 'array', // Mảng xác suất trúng thưởng
                'winning_probabilities.*' => 'numeric|min:0|max:100', // Kiểm tra xác suất trúng thưởng
            ]);

            // Tìm sự kiện cần cập nhật
            $event = Event::findOrFail($id);

            // Cập nhật thông tin sự kiện
            $event->update([
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active,
            ]);

            // Gán lại coupon cho sự kiện (nếu có)
            if ($request->has('coupons')) {
                // Detach all existing coupons
                $event->coupons()->detach();
                // Attach new coupons
                $event->coupons()->attach($request->coupons);
            }

            // Cập nhật winning_probability cho các coupon trong bảng coupons
            if ($request->has('winning_probabilities')) {
                foreach ($request->coupons as $index => $couponId) {
                    if (isset($request->winning_probabilities[$index])) {
                        // Cập nhật trực tiếp trên bảng coupons
                        Coupon::where('id', $couponId)->update([
                            'winning_probability' => $request->winning_probabilities[$index],
                        ]);
                    }
                }
            }

            // Lấy sự kiện và coupon sau khi cập nhật
            $eventWithCoupons = Event::with('coupons')->findOrFail($id);

            return new EventResource($eventWithCoupons);

        } catch (\Exception $e) {
            // Bắt lỗi nếu có ngoại lệ xảy ra
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi cập nhật sự kiện.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy(string $id)
    {
        try {
            $Event = Event::findOrFail($id);
            $Event->delete();
            return response()->json(['message' => 'event xóa thành công']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Xóa event thất bại', 'error' => $e->getMessage()], 500);
        }
    }
}
