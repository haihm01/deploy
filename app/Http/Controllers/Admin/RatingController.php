<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RatingController extends Controller
{

    // Lấy tất cả đánh giá
    public function getAllRating(Request $request)
    {
        try {
            $query = Rate::query();
    
            $ratings = $query->with(['product', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
    
            return response()->json($ratings);
        } catch (\Exception $e) {
            Log::error('Error fetching all ratings: ' . $e->getMessage());
    
            return response()->json(['message' => 'Đã xảy ra lỗi trong quá trình lấy đánh giá.'], 500);
        }
    }
    
    // Lấy limit 10 đánh giá mới nhất
    public function getLimitRating10(Request $request)
    {
        try {
            $query = Rate::query();
    
            $ratings = $query->with(['product', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
    
            return response()->json($ratings);
        } catch (\Exception $e) {
            Log::error('Error fetching the latest 10 ratings: ' . $e->getMessage());
    
            return response()->json(['message' => 'Đã xảy ra lỗi trong quá trình lấy đánh giá.'], 500);
        }
    }
    

    //Lấy đánh giá theo người dùng
    public function getRatingByUser()
{
    try {
        $ratings = Rate::with(['user', 'product'])
            ->select('user_id', 'product_id', 'content', 'rating')
            ->groupBy('user_id', 'product_id', 'content', 'rating')
            ->get();

        $result = [];

        foreach ($ratings as $rating) {
            $userId = $rating->user_id;

            if (!isset($result[$userId])) {
                $result[$userId] = [
                    'user' => $rating->user,
                    'products' => [],
                ];
            }

            $result[$userId]['products'][] = [
                'id' => $rating->product_id,
                'name' => $rating->product->name,
                'content' => $rating->content,
                'rating' => $rating->rating,
                'product' => $rating->product,
            ];
        }

        return response()->json(array_values($result));
    } catch (\Exception $e) {
        Log::error('Error fetching ratings by user: ' . $e->getMessage());

        return response()->json(['message' => 'Đã xảy ra lỗi trong quá trình lấy đánh giá.'], 500);
    }
}



    //Lấy đánh giá theo sản phẩm
    public function getRatingByProduct()
    {
        try {
            $ratings = Rate::with(['user', 'product'])
                ->select('product_id', 'user_id', 'content', 'rating')
                ->groupBy('product_id', 'user_id', 'content', 'rating')
                ->get();
    
            $result = [];
    
            foreach ($ratings as $rating) {
                $productId = $rating->product_id;
    
                if (!isset($result[$productId])) {
                    $result[$productId] = [
                        'product' => $rating->product,
                        'ratings' => [],
                    ];
                }
    
                $result[$productId]['ratings'][] = [
                    'user_id' => $rating->user_id,
                    'user' => $rating->user,
                    'content' => $rating->content,
                    'rating' => $rating->rating,
                ];
            }
    
            return response()->json(array_values($result));
        } catch (\Exception $e) {
            Log::error('Error fetching ratings by product: ' . $e->getMessage());
    
            return response()->json(['message' => 'Đã xảy ra lỗi trong quá trình lấy đánh giá.'], 500);
        }
    }
    

    // Lọc theo rating (số sao từ 1->5)
    public function filterRating(Request $request)
    {
        try {
            $request->validate([
                'rating' => 'nullable|integer|min:1|max:5',
            ]);
    
            $ratingFilter = $request->query('rating');
    
            $query = Rate::with(['user', 'product']);
    
            if ($ratingFilter) {
                $query->where('rating', $ratingFilter);
            }
    
            $ratings = $query->orderBy('created_at', 'desc')->get();
    
            return response()->json($ratings);
            
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json(['message' => 'Dữ liệu không hợp lệ.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            
            Log::error('Error filtering ratings: ' . $e->getMessage());
    
            return response()->json(['message' => 'Đã xảy ra lỗi trong quá trình lọc đánh giá.'], 500);
        }
    }
    

    // lấy rating theo id
    public function getOneRatingById($id)
    {
        try {
            $rating = Rate::with(['user', 'product'])->findOrFail($id);

            return response()->json($rating, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Đánh giá không tồn tại.'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching rating by ID: ' . $e->getMessage());

            return response()->json(['message' => 'Đã xảy ra lỗi trong quá trình lấy đánh giá.'], 500);
        }
    }



}
