<?php

namespace App\Http\Controllers\Client;

use App\Events\CheckExpiredSalePrices;
use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\RateAllByProductResource;
use App\Http\Resources\RateResource;
use App\Models\Address;
use App\Models\Answer;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Product;
use App\Models\Question;
use App\Models\Rate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{

    //get one getOneProductBySlug
    public function getOneProductBySlug(Request $request, string $slug)
    {
        try {

            // Lấy thông tin sản phẩm
            $product = Product::with('category', 'variants.attributeValues.attribute', 'variants.inventoryStock')
                ->where('slug', $slug)
                ->where('is_active', 1)
                ->firstOrFail();

                // $variant = $product->variants->first();

                // if ($variant) {
                //     event(new CheckExpiredSalePrices($variant));
                // } else {
                //     Log::warning('Không tìm thấy variant cho sản phẩm: ' . $slug);
                // }

            $relatedProducts = Product::with('category', 'variants.attributeValues.attribute')
                ->where('category_id', $product->category_id)
                ->where('is_active', true)
                ->where('id', '!=', $product->id)
                ->limit(5)
                ->get();



            // Tính trung bình số sao
            $averageRating = Rate::where('product_id', $product->id)
                ->average('rating');

            return response()->json([
                'product' => new ProductResource($product),
                'related_products' => ProductResource::collection($relatedProducts),
                // 'ratingslide10' => RateResource::collection($rates)
                'average_rating' => round($averageRating * 2) / 2,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Không tìm thấy sản phẩm.'], 404);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi xử lý thông tin sản phẩm:', [
                'slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Đã xảy ra lỗi khi xử lý thông tin sản phẩm.'], 500);
        }
    }

    //get wishlists by user
    public function getWishlists()
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Lấy danh sách wishlists và sản phẩm kèm theo
            $wishlists = $user->wishlists()->with('product.variants.attributeValues.attribute')->paginate(10);

            // Giải mã trường 'images' cho mỗi sản phẩm trong wishlist
            foreach ($wishlists as $wishlist) {
                if ($wishlist->product && $wishlist->product->images) {
                    $wishlist->product->images = json_decode($wishlist->product->images);
                }
            }

            return response()->json([
                'wishlists' => $wishlists
            ], 200);
        } else {
            return response()->json(['error' => 'Tài khoản chưa đăng nhập.'], 401);
        }
    }

        //get wishlists by check
        public function getWishlistsCheck()
        {
            if (auth()->check()) {
                $user = auth()->user();

                // Chỉ lấy danh sách wishlists không kèm product
                $wishlists = $user->wishlists()->select(['id', 'user_id', 'product_id'])->get();

                return response()->json([
                    'wishlists' => $wishlists
                ], 200);
            } else {
                return response()->json(['error' => 'Tài khoản chưa đăng nhập.'], 401);
            }
        }

    //insert wishlists by user
    public function toggleWishlist(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Tài khoản chưa đăng nhập.'], 401);
        }

        $user = auth()->user();

        // Validate request input
        $validatedData = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        // Check if product exists in the wishlist
        $wishlist = $user->wishlists()->where('product_id', $request->product_id)->first();

        if ($wishlist) {
            // If exists, remove it
            $wishlist->delete();
            return response()->json([
                'message' => 'Sản phẩm đã được xóa khỏi danh sách yêu thích.',
                'status' => 'removed'
            ], 200);
        }

        // If not exists, create new wishlist entry
        $wishlist = $user->wishlists()->create([
            'product_id' => $request->product_id,
        ]);

        return response()->json([
            'message' => 'Sản phẩm đã được thêm vào danh sách yêu thích.',
            'status' => 'added',
            'wishlist' => $wishlist
        ], 201);
    }




    //delete wishlists by user
    public function deleteWishlist($product_id)
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Kiểm tra xem sản phẩm có trong danh sách yêu thích của người dùng không
            $wishlist = $user->wishlists()->where('product_id', $product_id)->first();

            if (!$wishlist) {
                return response()->json(['error' => 'Sản phẩm không tồn tại trong danh sách yêu thích.'], 404);
            }

            $wishlist->delete();

            return response()->json(['message' => 'Sản phẩm đã được xóa khỏi danh sách yêu thích.'], 200);
        } else {
            return response()->json(['error' => 'Tài khoản chưa đăng nhập.'], 401);
        }
    }


    // getProductsByCategory
    public function getProductsByCategory(int $categoryId)
    {
        try {
            $category = Category::with('product.variants.attributeValues.attribute')->findOrFail($categoryId);

            $products = Product::with('category', 'variants.attributeValues.attribute')
                ->where('category_id', $categoryId)
                ->where('is_active', true) // Điều kiện kiểm tra sản phẩm phải active
                ->paginate(10);

            return response()->json([
                // 'category' => new CategoryResource($category),
                'data' => ProductResource::collection($products),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Không tìm thấy danh mục.'], 404);
        } catch (\Throwable $e) {
            Log::error('Lỗi khi lấy sản phẩm theo danh mục: ' . $e->getMessage(), ['category_id' => $categoryId]);
            return response()->json(['error' => 'Đã xảy ra lỗi khi lấy sản phẩm theo danh mục.'], 500);
        }
    }

    // Lọc sản phẩm nổi bật
    public function getFeaturedProducts(): JsonResponse
    {
        try {
            $featuredProducts = Product::with('category', 'variants.attributeValues.attribute')
                ->where('is_featured', true)
                ->where('is_active', true) // Điều kiện kiểm tra sản phẩm phải active
                ->limit(10)
                ->get();
            if ($featuredProducts->isEmpty()) {
                return response()->json([
                    'message' => 'Không có sản phẩm nổi bật nào.',
                ], 404);
            }

            return response()->json(ProductResource::collection($featuredProducts), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi truy xuất sản phẩm nổi bật.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Lọc sản phẩm đang sale
    // public function getGoodDealProducts(): JsonResponse
    // {
    //     try {
    //         $goodDealProducts = Product::with('category', 'variants.attributeValues.attribute')
    //             ->where('is_good_deal', true)
    //             ->where('is_active', true) // Điều kiện kiểm tra sản phẩm phải active
    //             ->limit(10)
    //             ->get();

    //         if ($goodDealProducts->isEmpty()) {
    //             return response()->json([
    //                 'message' => 'Không có sản phẩm đang sale nào.',
    //             ], 404);
    //         }

    //         return response()->json(ProductResource::collection($goodDealProducts), 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Có lỗi xảy ra khi truy xuất sản phẩm sale.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }





    // blog home
    public function listBlogs()
    {
        $blogs = Blog::latest('id')->paginate(10);

        return BlogResource::collection($blogs);
    }
    // end blog home
    // detailBlog
    public function detailBlog($slug)
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();

        $related_blogs = Blog::where('id', '!=', $blog->id)
            ->take(5)
            ->get();

        return response()->json([
            'blog' => new BlogResource($blog),
            'related_blogs' => BlogResource::collection($related_blogs),
        ]);
    }
    // end detailBlog


    //Coupon
    public function getCouponHome()
    {
        try {
            $data = Coupon::query()
                ->where('status', true)
                ->where('usage_limit', '>', 0)
                ->where('end_date', '>', Carbon::now())
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

    public function getCouponCart(Request $request)
    {
        try {
            // Lấy user id từ request
            $user = $request->user();

            // Lấy danh sách coupon_id đã được user sử dụng
            $usedCouponIds = CouponUser::where('user_id', $user->id)
                ->whereNotNull('used_at')
                ->pluck('coupon_id')
                ->toArray();

            $query = Coupon::query()
                ->where('status', true)
                ->where('usage_limit', '>', 0)
                ->where(function ($q) {
                    $q->where(function ($subQ) {
                        // Trường hợp có thời hạn
                        $subQ->where('start_date', '<', Carbon::now())
                            ->where('end_date', '>', Carbon::now());
                    })->orWhere(function ($subQ) {
                        // Trường hợp không có thời hạn
                        $subQ->whereNull('start_date')
                            ->whereNull('end_date');
                    });
                })
                ->whereNotIn('id', $usedCouponIds);

            // Xử lý điều kiện min và max order value
            $query->where(function ($q) use ($request) {
                $q->where(function ($subQuery) use ($request) {
                    // Trường hợp 1: Không có cả min và max (null)
                    $subQuery->whereNull('min_order_value')
                        ->whereNull('max_order_value');
                })->orWhere(function ($subQuery) use ($request) {
                    // Trường hợp 2: Chỉ có min, không có max
                    $subQuery->where('min_order_value', '<=', $request->totalCart)
                        ->whereNull('max_order_value');
                })->orWhere(function ($subQuery) use ($request) {
                    // Trường hợp 3: Có min, có max
                    $subQuery->where('min_order_value', '<=', $request->totalCart)
                        ->where('max_order_value', '>=', $request->totalCart);
                })->orWhere(function ($subQuery) use ($request) {
                    // Trường hợp 4: Không có min, chỉ có max
                    $subQuery->whereNull('min_order_value')
                        ->where('max_order_value', '>=', $request->totalCart);
                });
            });

            // Debug query
            \Log::info($query->toSql());
            \Log::info($query->getBindings());

            $data = $query->get();

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

    // FAQ
    // Lấy ra các câu hỏi đầu tiên
    public function getListFirstQuestion()
    {
        $questions = Question::where('answer_id', null)->get();
        return response()->json($questions);
    }

    // Lấy ra câu hỏi theo câu trả lời
    public function getQuestionByAnswer(string $id)
    {
        $questions = Question::where('answer_id', $id)->get();
        return response()->json($questions);
    }

    // Lấy ra câu trả lời theo câu hỏi
    public function getAnswerByQuestion(string $id)
    {
        $answers = Answer::where('question_id', $id)->get();
        return response()->json($answers);
    }


    public function ratingListAllbyProductToSlug(Request $request, $slug)
    {
        // Lấy thông tin sản phẩm từ slug
        $product = Product::where('slug', $slug)->firstOrFail();

        // Tính trung bình số sao
        $averageRating = Rate::where('product_id', $product->id)
            ->average('rating');

        // Đếm số lượng đánh giá theo từng mức sao
        $ratingCounts = $this->getRatingCounts($product->id);

        // Lấy danh sách đánh giá có phân trang
        $formattedPagedRates = $this->getPagedRatings($request, $product);

        return [
            'ratings' => [
                'average_rating' => round($averageRating * 2) / 2,
                'rating_counts' => [
                    ['key' => 'Tất cả'],
                    ['key' => '5', 'value' => $ratingCounts[5] ?? 0],
                    ['key' => '4', 'value' => $ratingCounts[4] ?? 0],
                    ['key' => '3', 'value' => $ratingCounts[3] ?? 0],
                    ['key' => '2', 'value' => $ratingCounts[2] ?? 0],
                    ['key' => '1', 'value' => $ratingCounts[1] ?? 0],
                ],
                'rate_paginate8' => $formattedPagedRates,
            ]
        ];
    }





    // Hàm phụ để lấy số lượng đánh giá theo từng sao
    private function getRatingCounts($productId)
    {
        $ratingCounts = Rate::where('product_id', $productId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Đảm bảo có đủ các mức sao từ 1-5
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($ratingCounts[$i])) {
                $ratingCounts[$i] = 0;
            }
        }

        return $ratingCounts;
    }

    // Hàm phụ để lấy danh sách đánh giá có phân trang
    private function getPagedRatings(Request $request, Product $product)
    {
        $ratingFilter = $request->input('ratingFilter');

        $rateQuery = Rate::with([
            'user:id,name,avatar',
            'product:id,name,slug',
            'order.items' => function ($query) use ($product) {
                $query->whereHas('variant', function ($q) use ($product) {
                    $q->where('product_id', $product->id);
                });
            },
            'order.items.variant.attributeValues.attribute'
        ])
            ->where('product_id', $product->id);

        if (in_array($ratingFilter, [1, 2, 3, 4, 5])) {
            $rateQuery->where('rating', $ratingFilter);
        }

        $pagedRates = $rateQuery->orderByDesc('created_at')->paginate(8);

        return $pagedRates->through(function ($rate) {
            // Lấy tất cả các items có variant thuộc sản phẩm được đánh giá
            $attributeValuesList = collect($rate->order?->items)
                ->where('variant.product_id', $rate->product_id)
                ->flatMap(function ($orderItem) {
                    // Map attributeValues cho từng variant
                    return $orderItem->variant->attributeValues
                        ->map(function ($attrValue) {
                            return [
                                'id' => $attrValue->id,
                                'value' => $attrValue->value,
                                'attribute' => [
                                    'id' => $attrValue->attribute->id,
                                    'name' => $attrValue->attribute->name,
                                ]
                            ];
                        })
                        ->sortBy(function ($item) {
                            // Sắp xếp để Size luôn ở trước Color
                            return $item['attribute']['name'] === 'Size' ? 0 : 1;
                        })
                        ->values()
                        ->all();
                })
                ->values()
                ->all();

            return [
                'id' => $rate->id,
                'content' => $rate->content,
                'rating' => $rate->rating,
                'created_at' => $rate->created_at,
                'user' => [
                    'name' => $rate->user->name,
                    'avatar' => $rate->user->avatar,
                ],
                'attribute_values' => $attributeValuesList
            ];
        });
    }
    // Lấy ra các địa chỉ user thêm
    public function getAllAddress()
    {
        $user = Auth::id();
        $address = Address::where('user_id', $user)->get();
        return response()->json([
            'data' => $address
        ]);
    }
    public function getAddress(string $id)
    {
        $address = Address::where('id', $id)->get();
        return response()->json([
            'data' => $address
        ]);
    }

    // Thêm địa chỉ
    public function addAddress(Request $request)
    {
        try {
            // Đếm số địa chỉ hiện tại của user
            $addressCount = Address::where('user_id', Auth::id())->count();

            // Kiểm tra nếu đã có 5 địa chỉ
            if ($addressCount >= 5) {
                return response()->json([
                    'error' => 'Bạn chỉ được thêm tối đa 5 địa chỉ'
                ], 400);
            }

            // Nếu chưa đủ 5 địa chỉ thì tạo mới
            $address = Address::create([
                'fullname' => $request->fullname,
                'phone' => $request->phone,
                'province' => $request->province,
                'district' => $request->district,
                'ward' => $request->ward,
                'address' => $request->address,
                'user_id' => Auth::id()
            ]);

            // Kiểm tra xem có phải địa chỉ đầu tiên của thằng này không
            $userAddressCount = Address::where('user_id', Auth::id())->count();

            // Nếu đúng thì đặt nó làm mặc định
            if ($userAddressCount === 1) {
                $user = Auth::user();
                $user->address_id = $address->id;
                $user->save();
            }

            return response()->json([
                'data' => $address
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }
    // Sửa địa chỉ
    public function editAddress(Request $request, string $id)
    {
        try {
            $address = Address::findOrFail($id);

            $updateAddress = $address->update([
                'fullname' => $request->fullname,
                'phone' => $request->phone,
                'province' => $request->province,
                'district' => $request->district,
                'ward' => $request->ward,
                'address' => $request->address,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'data' => $address
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }
    // Xóa địa chỉ
    public function deleteAddress(string $id)
    {
        try {
            $address = Address::findOrFail($id);
            $address->delete();
            return response()->json(['message' => 'Địa chỉ đã được xóa thành công']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }

    }
    // Thiết lập
    public function updateDefaultAddress(int $id)
    {
        try {
            $address = Address::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $user = Auth::user();

            $user->address_id = $id;
            $user->save();

            return response()->json([
                'message' => 'Địa chỉ mặc định đã được cập nhật',
                'default_address_id' => $id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Không thể cập nhật địa chỉ mặc định: ' . $e->getMessage()
            ], 500);
        }
    }
}
