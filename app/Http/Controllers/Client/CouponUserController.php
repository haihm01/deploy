<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Event;
use App\Models\EventCoupon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CouponUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = CouponUser::query()
            ->with(['coupon','user'])
            ->where('user_id', Auth::id())
            ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);

            return response()->json([
                'message' => 'Đã có lỗi. Vui lòng thử lại',
                'status' => 'error',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $data['user_id'] = Auth::id();
            $data['coupon_id'] = $request->coupon_id;

            $voucher = CouponUser::create($data);

            return response()->json([
                'status' => 'success',
                'data' => $voucher,
                'message' => 'Đã lưu voucher'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);

            return response()->json([
                'message' => 'Đã có lỗi. Vui lòng thử lại',
                'status' => 'error',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        try {
            $couponUser = CouponUser::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

            $couponUser->update([
                'used_at' => now(),
            ]);
            return response()->json([
                'data' => $couponUser,
                'status' => 'success'
            ], 200);


        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);

            return response()->json([
                'message' => 'Đã có lỗi. Vui lòng thử lại',
                'status' => 'error',

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */


     public function claimCoupon(Request $request, $eventId, $couponId)
     {
         $user = $request->user();

         $eventCouponExists = EventCoupon::where('event_id', $eventId)
             ->where('coupon_id', $couponId)
             ->exists();

         if (!$eventCouponExists) {
             return response()->json(['message' => 'Coupon không thuộc sự kiện này.'], 404);
         }

         $coupon = Coupon::find($couponId);
         if (!$coupon || $coupon->usage_limit <= 0) {
             return response()->json(['message' => 'Coupon không còn số lượng.'], 404);
         }

         $couponUser = CouponUser::create([
             'user_id' => $user->id,
             'coupon_id' => $couponId,
         ]);

         $coupon->decrement('usage_limit');

         return response()->json([
             'message' => 'Bạn đã nhận coupon thành công!',
             'coupon_user_id' => $couponUser->id,
         ], 201);
     }


}
