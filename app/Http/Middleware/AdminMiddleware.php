<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            if($user->role == 'admin'){
                return $next($request);
            }
            
            return response()->json(['message' => 'Unauthorized. You do not have the required permissions.'], 403);

        } catch (\Exception $e) {
            Log::error('Error', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }
}