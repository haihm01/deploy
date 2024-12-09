<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserRoleUpdated;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Lấy tất cả thông tin user
    public function index(Request $request)
    {
        $currentUserId = $request->user()->id;

        $users = User::query()
            ->where('id', '!=', $currentUserId)
            ->paginate(10);

        return response()->json($users, 200);
    }
    //detail user
    public function show(string $id)
    {
        $users = User::query()->findOrFail($id);
        return response()->json($users, 200);
    }
    
    public function updateRole(Request $request, $id)
    {
        try {
            $role = $request->input('role');
             
            $user = User::find($id);
             
            if (!$user) {
                return response()->json(['message' => 'Người dùng không tồn tại.'], 404);
            }
             
            $user->role = $role;
            $user->save();
            
            // Broadcast event
            event(new UserRoleUpdated($user));

            return response()->json([
                'message' => 'Cập nhật quyền thành công.',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật quyền.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
