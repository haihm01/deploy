<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\UserSpin;
use Illuminate\Http\Request;

class SpinController extends Controller
{
    public function resetDailySpins(Request $request)
    {
        try {
            $user = $request->user(); // Lấy thông tin người dùng hiện tại
            $today = now()->toDateString(); // Ngày hiện tại

            // Lấy thông tin lượt quay của người dùng
            $userSpin = UserSpin::firstOrCreate(
                ['user_id' => $user->id], // Nếu chưa có record thì tạo mới
                ['remaining_spins' => 2, 'last_spin_date' => $today] // Tạo mới với 2 lượt quay và ngày hiện tại
            );

            // Kiểm tra nếu ngày hiện tại khác với ngày cuối cùng quay
            if ($userSpin->last_spin_date < $today) {
                // Reset lại lượt quay cho ngày mới
                $userSpin->update([
                    'remaining_spins' => 2,
                    'last_spin_date' => $today,
                ]);

                return response()->json([
                    'message' => 'Lượt quay đã được reset thành công!',
                    'remaining_spins' => $userSpin->remaining_spins,
                    'last_spin_date' => $userSpin->last_spin_date,
                ]);
            }

            return response()->json([
                'message' => 'Bạn đã xác nhận reset lượt quay hôm nay rồi.',
                'remaining_spins' => $userSpin->remaining_spins,
                'last_spin_date' => $userSpin->last_spin_date,
            ], 400);
        } catch (\Exception $e) {
            // Bắt lỗi nếu có ngoại lệ xảy ra
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi xử lý yêu cầu.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    public function spin(Request $request)
    {
        try {
            $user = $request->user(); // Lấy thông tin người dùng hiện tại
            $today = now()->toDateString();

            // Lấy thông tin lượt quay của người dùng
            $userSpin = UserSpin::where('user_id', $user->id)->first();

            if (!$userSpin || $userSpin->last_spin_date !== $today) {
                return response()->json([
                    'message' => 'Vui lòng reset lượt quay trước khi tiếp tục.'
                ], 400);
            }

            // Kiểm tra số lượng lượt quay còn lại
            if ($userSpin->remaining_spins <= 0) {
                return response()->json([
                    'message' => 'Bạn đã hết lượt quay trong ngày hôm nay.'
                ], 400);
            }

            // Thực hiện quay (Logic quay)

            // Giảm số lượt quay còn lại
            $userSpin->decrement('remaining_spins');

            return response()->json([
                'message' => 'Quay thành công!',
                'remaining_spins' => $userSpin->remaining_spins,
            ]);
        } catch (\Exception $e) {
            // Bắt lỗi nếu có ngoại lệ xảy ra
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi xử lý yêu cầu.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
