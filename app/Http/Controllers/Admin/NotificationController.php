<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function getUserNotifications($userId)
    {
        try {
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông báo'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markAsRead(string $id)
    {
        try {
            Notification::where('id', $id)->update([
                'is_read' => true
            ]);            

            return response()->json([
                'success' => true,
                'message' => 'Đã đánh dấu đã đọc'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thông báo'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
