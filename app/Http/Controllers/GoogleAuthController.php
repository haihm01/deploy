<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    // Điều hướng đến Google để đăng nhập
    public function redirectToGoogle()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    }


    // Xử lý callback từ Google
    public function handleGoogleCallback()
    {
        try {
            // Lấy thông tin người dùng từ Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Tìm hoặc tạo người dùng
            $user = User::firstOrCreate(
                ['provider_id' => $googleUser->getId()],
                [
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null, // không cần mật khẩu cho OAuth
                ]
            );

            // Đăng nhập người dùng
            Auth::login($user);

            // Tạo token cho người dùng
            $token = $user->createToken('GoogleLoginToken')->plainTextToken;

            // Trả về JSON chứa token và thông tin người dùng
            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            // Xử lý lỗi khi không thể lấy thông tin người dùng
            return response()->json([
                'message' => 'Đăng nhập Google thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    public function redirectToFacebook()
{
    $url = Socialite::driver('facebook')->stateless()->redirect()->getTargetUrl();

    return response()->json(['url' => $url]);
}

public function handleFacebookCallback()
{
    try {

        $SocialUser = Socialite::driver('facebook')->stateless()->user();

        $user = User::updateOrCreate(
            [
                'provider_id' => $SocialUser->id,
                'provider' => 'facebook'
            ],
            [
                'name' => $SocialUser->name,
                'email' => $SocialUser->email,
                'provider_token' => $SocialUser->token,
                'avatar' => $SocialUser->avatar,
                'password' => null,
            ]
        );

        Auth::login($user);

        $token = $user->createToken('SocialLoginToken')->plainTextToken;


        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    } catch (\Exception $e) {

        return response()->json([
            'message' => 'Đăng nhập thất bại',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
