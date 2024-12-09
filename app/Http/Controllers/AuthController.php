<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RequestPasswordResetRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use App\Notifications\ResetPasswordNotification;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        try {
            $request->validated($request->only(['email', 'password']));

            if (!Auth::attempt($request->only(['email', 'password']))) {
                return $this->error([], 'Tài khoản hoặc mật khẩu không chính xác', 401);
            }

            $user = User::where('email', $request->email)->first();

            // Xóa các token của tài khoản trước đó
            // $user->tokens()->delete();
            $newToken = $user->createToken('API Token', ['*'], now()->addHours(2));
            $token = $newToken->plainTextToken;

            return response()->json([
                'message' => 'Đăng nhập thành công!',
                'user' => $user,
                'token' => $token
            ], 200);
        } catch (\Exception $e) {
            Log::error('Có lỗi xảy ra: ' . $e->getMessage());
            return response()->json([
                'message' => 'Có lỗi xảy ra trong quá trình đăng nhập.'
            ], 500);
        }
    }


    public function register(RegisterRequest $request)
    {
        try {
            $request->validated($request->all());

            $existingUser = User::where('email', $request->email)->first();

            if ($existingUser) {
                if (is_null($existingUser->password)) {
                    $existingUser->update([
                        'name' => $request->name,
                        'password' => bcrypt($request->password)
                    ]);

                    return response()->json([
                        'message' => 'Đăng ký thành công!',
                        'user' => $existingUser,
                        'token' => $existingUser->createToken('API Token')->plainTextToken
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Email này đã được sử dụng, không thể đăng ký.'
                    ], 400);
                }
            }

            // Nếu email chưa tồn tại, tạo người dùng mới
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);

            return response()->json([
                'message' => 'Đăng ký thành công!',
                'user' => $user,
                'token' => $user->createToken('API Token')->plainTextToken
            ], 200);

        } catch (\Exception $e) {
            Log::error('Có lỗi xảy ra: ' . $e->getMessage());
            return response()->json([
                'message' => 'Có lỗi xảy ra trong quá trình đăng ký.'
            ], 500);
        }
    }


    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đăng Xuất thành công!'
        ], 200);
    }

    public function requestPasswordReset(RequestPasswordResetRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Email không tồn tại trong hệ thống.'
                ], 404);
            }

            $token = app('auth.password.broker')->createToken($user);

            $user->notify(new ResetPasswordNotification($token, $request->email));

            return response()->json([
                'message' => 'Link đổi mật khẩu đã được gửi đến email của bạn.',
                'token' => $token,
                'email' => $request->email
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }


    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->password = bcrypt($password);
                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'message' => 'Mật khẩu đã được thay đổi thành công.'
                ], 200);
            }

            return response()->json([
                'message' => 'Có lỗi xảy ra trong quá trình đặt lại mật khẩu.'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    // đổi mật khẩu

    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|',
            ]);

            $user = $request->user();

            if ($request->current_password === $request->new_password) {
                return response()->json(['message' => 'Mật khẩu mới không thể trùng với mật khẩu hiện tại'], 400);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Mật khẩu hiện cũ tại không chính xác'], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['message' => 'Đổi mật khẩu thành công'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra trong quá trình đổi mật khẩu',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    public function updateProfile(Request $request)
    {
        try {
            $request->validate([
                'name' => 'nullable|string|max:255',
                'avatar' => 'nullable|string|max:255',
                'tel' => 'nullable|string|max:20',
            ]);

            $user = $request->user();

            $user->name = $request->input('name');
            $user->avatar = $request->input('avatar');
            $user->tel = $request->input('tel');
            $user->save();

            return response()->json([
                'message' => 'Cập nhật thông tin thành công',
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra trong quá trình cập nhật thông tin',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'message' => 'Lấy thông tin người dùng thành công',
                'user' => [
                    'name' => $user->name,
                    'avatar' =>$user->avatar,
                    'tel' => $user->tel,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy thông tin người dùng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
