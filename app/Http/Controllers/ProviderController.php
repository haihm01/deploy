<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback($provider)
    {
        try {
            // Lấy thông tin người dùng từ nhà cung cấp
            $SocialUser = Socialite::driver($provider)->stateless()->user();

            $user = User::where('email', $SocialUser->email)->first();

            if ($user) {
                // Nếu người dùng đã tồn tại, cập nhật thông tin
                $user->update([
                    'provider_id' => $SocialUser->id,
                    'provider' => $provider,
                    'provider_token' => $SocialUser->token,
                ]);
            } else {
                // Nếu người dùng chưa tồn tại, tạo mới
                $user = User::create([
                    'provider_id' => $SocialUser->id,
                    'provider' => $provider,
                    'name' => $SocialUser->name,
                    'nickname' => $SocialUser->nickname,
                    'email' => $SocialUser->email,
                    'provider_token' => $SocialUser->token,
                    'avatar' => $SocialUser->avatar,
                    'password' => null,
                ]);
            }

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
