<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\admin\EventController;
use App\Http\Controllers\Admin\FilterStockController;
use App\Http\Controllers\Admin\InventoryImportController;
use App\Http\Controllers\Admin\InventoryStockController;
use App\Http\Controllers\Admin\ModelController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\RatingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RoleHasModelController;
use App\Http\Controllers\Admin\StatisticalController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Client\CouponUserController;
use App\Http\Controllers\Client\FilterController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\client\PaymentController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\Client\OderCheckController;
use App\Http\Controllers\Client\OrderController;
use App\Http\Controllers\client\ChatbotController;
use App\Http\Controllers\Client\EventSpinController;
use App\Http\Controllers\Client\ReviewController;
use App\Http\Controllers\client\SpinController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//create cart
Route::post('/addcartMultil/{id_user}', [CartController::class, 'addCartMultil']);
Route::get('/variant/{id_variant}', [CartController::class, 'getVariant'])->name('variant.get');

// Đăng ký đăng nhập
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Quên mật khẩu
Route::post('/auth/password/request-reset', [AuthController::class, 'requestPasswordReset'])->name('password.request');
Route::post('/auth/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');

// Đăng nhập mạng xã hội
Route::get('/auth/{provider}/redirect', [ProviderController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [ProviderController::class, 'callback']);

// Sản phẩm
Route::get('home/product/{slug}', [HomeController::class, 'getOneProductBySlug']);
Route::get('home/products/{slug}/ratings', [HomeController::class, 'ratingListAllbyProductToSlug']);

Route::get('home/products/featured', [HomeController::class, 'getFeaturedProducts']);
Route::get('home/products/good-deal', [HomeController::class, 'getGoodDealProducts']);
Route::get('home/product/category/{id}', [HomeController::class, 'getProductsByCategory']);



Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

Route::get('auth/facebook', [GoogleAuthController::class, 'redirectToFacebook']);
Route::get('auth/facebook/callback', [GoogleAuthController::class, 'handleFacebookCallback']);


//filter
Route::get('products/filter', [FilterController::class, 'getFilter']);
Route::post('products/filter', [FilterController::class, 'filter']);

//Blog
Route::get('/list-blogs', [HomeController::class, 'listBlogs'])->name('blogs.listBlogs');
Route::get('/detailBlog/{slug}', [HomeController::class, 'detailBlog'])->name('blog.detailBlog');

//Checkoder
Route::get('check-order', [OderCheckController::class, 'checkOrder'])->name('order.check');

// Coupon
Route::get('/coupon-home', [HomeController::class, 'getCouponHome']);

// Chat bot
Route::get('/first-question', [HomeController::class, 'getListFirstQuestion']);
Route::get('/question-by-answer/{id}', [HomeController::class, 'getQuestionByAnswer']);
Route::get('/answer-by-question/{id}', [HomeController::class, 'getAnswerByQuestion']);

// Quyền khi đăng nhập
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/get-all-address', [HomeController::class, 'getAllAddress']);
    Route::get('/get-address/{id}', [HomeController::class, 'getAddress']);
    Route::post('/add-address', [HomeController::class, 'addAddress']);
    Route::put('/edit-address/{id}', [HomeController::class, 'editAddress']);
    Route::delete('/delete-address/{id}', [HomeController::class, 'deleteAddress']);
    Route::patch('/update-default-address/{id}', [HomeController::class, 'updateDefaultAddress']);

    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Thông báo
    Route::get('/notification/{id}', [NotificationController::class, 'getUserNotifications']);
    Route::patch('/notification/{id}/read', [NotificationController::class, 'markAsRead']);

    // Admin
    Route::middleware(['admin'])->group(function () {
        //QL user
        // Lấy tất cả thông tin user
        Route::get('/users', [UserController::class, 'index']);
        // Cập nhật role của user
        Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);
        Route::get('/user/{id}', [UserController::class, 'show']);

        // QL banner
        // Route::put('createBannerMultiple', [BannerController::class, 'createBannerMultiple']);
        // Route::post('updateBannerMultiple', [BannerController::class, 'updateBannerMultiple']);

        //QL Event
        Route::get('/admin/events/coupons', [EventController::class, 'getEventCoupons']);
        Route::post('/admin/events', [EventController::class, 'createEvent']);
        Route::get('/admin/showEvent/{id}', [EventController::class, 'showEvent']);

        Route::put('/admin/updateEvent/{id}', [EventController::class, 'updateEvent']);
        Route::delete('/admin/events/{id}', [EventController::class, 'destroy']);
        //list danh sách các coupon type event
        Route::get('/admin/coupons/events', [EventController::class, 'getAllEventCoupons']);

        // Quản lý đơn hàng
        Route::get('admin/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index']);
        Route::get('admin/order-detail/{id}', [\App\Http\Controllers\Admin\OrderController::class, 'orderDetail']);
        Route::patch('admin/order-detail/{id}', [\App\Http\Controllers\Admin\OrderController::class, 'updateOrderDetail']);
        Route::patch('admin/order-cancelation/{id}', [\App\Http\Controllers\Admin\OrderController::class, 'canceledOrder']);
        Route::post('admin/order-update_much', [\App\Http\Controllers\Admin\OrderController::class, 'updateMuch']);

        //lịch sử nhập hàng
        Route::get('checkAvailableStock', [InventoryImportController::class, 'checkAvailableStock']);
        //tìm hàng theo mã
        Route::get('/search-by-batch-number/{batchNumber}', [InventoryImportController::class, 'searchByBatchNumber']);
        //xóa đơn nhập
        Route::delete('/deleteImport/{id}', [InventoryImportController::class, 'deleteImport']);
        Route::post('/inventory-imports/{id}/restore', [InventoryImportController::class, 'restoreImport']);
        //lịch sử nhập hàng trước nhập hàng
        Route::get('/inventory-import/variant/{variantId}', [InventoryImportController::class, 'getByVariantId']);
        //xóa lịch sử nhập hàng
        Route::delete('/deleteHistoryRecord/{id}', [InventoryImportController::class, 'deleteHistoryRecord']);
        // ví dụ ?from_date=2024-12-01&to_date=2024-12-10
        Route::get('/checkDateHistore', [InventoryImportController::class, 'checkDateHistore']);

        // Nhập hàng
        // Route::put('/updateVariant', [InventoryImportController::class, 'updateVariant']);
        // Route::put('/UpdateMultiple', [InventoryImportController::class, 'UpdateMultiple']);
        Route::post('/import-inventory', [InventoryImportController::class, 'importExcel']);

        Route::get('/productsWithInventoryImports', [InventoryImportController::class, 'productsWithInventoryImports']);
        Route::get('/getAllProductNoImport', [InventoryImportController::class, 'getAllProductNoImport']);
        Route::post('/import-orders', [InventoryImportController::class, 'import']);
        Route::post('/import-multiple-orders', [InventoryImportController::class, 'importMultiple']);
        Route::get('/list-import', [InventoryImportController::class, 'index']);
        Route::get('/list-stock', [InventoryStockController::class, 'index']);
        Route::get('/import-detail/{id}', [InventoryImportController::class, 'getDetailImport']);


        // Nhà cung cấp
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::get('/supplier/{id}', [SupplierController::class, 'show']);
        Route::post('/store-supplier', [SupplierController::class, 'store']);
        Route::put('/update-supplier/{id}', [SupplierController::class, 'update']);
        Route::delete('/delete-supplier/{id}', [SupplierController::class, 'delete']);

        // filter stock
        Route::get('/filter-stock', [FilterStockController::class, 'getCategoryFilter']);
        Route::post('/filter-stock', [FilterStockController::class, 'filterStock']);
    });
    // Admin & Manage
    Route::middleware(['manage'])->group(function () {
        // QL FAQ
        Route::controller(FaqController::class)->prefix('faq/')->group(function () {
            Route::get('list-question', [FaqController::class, 'listQuestions']);
            Route::post('store-question', [FaqController::class, 'storeQuestions']);
            Route::delete('delete-question/{id}', [FaqController::class, 'deleteQuestion']);
            Route::get('list-answer', [FaqController::class, 'listAnswers']);
            Route::post('store-answer', [FaqController::class, 'storeAnswers']);
            Route::delete('delete-answer/{id}', [FaqController::class, 'deleteAnswer']);
        });

        //Thống kê
        Route::get('thong-ke/doanh-thu', [StatisticalController::class, 'doanhThu']);
        Route::get('thong-ke/san-pham', [StatisticalController::class, 'thongKeSanPham']);
        Route::get('thong-ke/don-hang', [StatisticalController::class, 'thongKeDonHang']);
        Route::get('thong-ke/ngay-thong-ke', [StatisticalController::class, 'NgayThongKe']);
        Route::get('thong-ke/thong-ke-theo-ngay', [StatisticalController::class, 'thongKeNgay'])->name('thongKeNgay');
        Route::get('thong-ke/so-luong-bien-the-duoi-10', [StatisticalController::class, 'listSoLuongBienTheDuoi10']);
        Route::get('thong-ke/so-luong-bien-the-da-het', [StatisticalController::class, 'listSoLuongBienTheDaHet']);
        Route::get('thong-ke/profit', [StatisticalController::class, 'profit']);

        // QL danh mục
        Route::apiResource('category', CategoryController::class);
        Route::patch('category/{id}/is-active', [CategoryController::class, 'updateIsActive'])->name('category.updateIsActive');
        Route::post('category/delete-much', [CategoryController::class, 'deleteMuch'])->name('category.deleteMuch');
        Route::patch('category/restore/{id}', [CategoryController::class, 'restore'])->name('category.restore');
        Route::delete('category/hard-delete/{id}', [CategoryController::class, 'hardDelete'])->name('category.hardDelete');
        Route::get('category/{id}/product-count', [CategoryController::class, 'countProducts']);

        // QL thuộc tính
        Route::apiResource('attribute', AttributeController::class);
        Route::get('admin/attribute/{id}', [AttributeController::class, 'getAttributeDetail']);
        Route::put('attributes/{id}/type', [AttributeController::class, 'updateType']);
        Route::apiResource('attribute-value', AttributeValueController::class);
        Route::get('/attribute-values/{id}', [AttributeValueController::class, 'getByAttributeId']);
        Route::put('/attribute-values/{attributeValue}', [AttributeValueController::class, 'update']);

        // QL sản phẩm
        Route::get('/product/{slug}', [ProductController::class, 'findBySlug']);
        Route::apiResource('products', ProductController::class);
        Route::post('/products/restore-multiple', [ProductController::class, 'restoreMultiple']);
        Route::get('listDelete', [ProductController::class, 'listDelete']);
        Route::patch('product/{id}/is_featured', [ProductController::class, 'updateIsFeatured'])->name('category.updateIsFeatured');
        // Route::patch('product/{id}/is_good_deal', [ProductController::class, 'updateIsGoodDeal'])->name('category.updateIsGoodDeal');
        Route::patch('product/{id}/is_active', [ProductController::class, 'updateIsActive'])->name('category.updateIsActive');
        Route::patch('product/restore/{id}', [ProductController::class, 'restore'])->name('product.restore');
        Route::delete('product/hard-delete/{id}', [ProductController::class, 'hardDelete'])->name('product.hardDelete');

        // QL banner
        Route::apiResource('banners', BannerController::class);
        Route::patch('banners/{id}/is-active', [BannerController::class, 'updateIsActive'])->name('blogs.updateIsActive');

        // QL blog
        Route::apiResource('blogs', BlogController::class);
        Route::patch('blogs/{id}/is-active', [BlogController::class, 'updateIsActive'])->name('blogs.updateIsActive');

        // QL Rating
        Route::get('ratings', [RatingController::class, 'getAllRating']);
        Route::get('ratings/limit10', [RatingController::class, 'getLimitRating10']);
        Route::get('ratings/by-user', [RatingController::class, 'getRatingByUser']);
        Route::get('ratings/by-product', [RatingController::class, 'getRatingByProduct']);
        Route::get('ratings/filter', [RatingController::class, 'filterRating']);
        Route::get('ratings/{id}', [RatingController::class, 'getOneRatingById'])->name('ratings.getOne');
        
        // QL mã giảm giá
        Route::apiResource('coupon', CouponController::class);
        Route::patch('coupon/{id}/status', [CouponController::class, 'updateStatus'])->name('coupon.updateStatus');
        Route::patch('coupon/{id}/is_featured', [CouponController::class, 'updateIsFeatured'])->name('coupon.updateIsFeatured');
    });

    // Giỏ hàng_user
    Route::apiResource('cart', CartController::class);
    Route::patch('/cart/{id}/{operation?}', [CartController::class, 'update']);
    Route::post('cart/delete-much', [CartController::class, 'deleteMuch'])->name('cart.deleteMuch');

    //Wishlist_user
    Route::get('/list-wishlists', [HomeController::class, 'getWishlists']);
    Route::get('/list-wishlists-check', [HomeController::class, 'getWishlistsCheck']);
    Route::post('/toogle-wishlists', [HomeController::class, 'toggleWishlist']);


    // Order
    Route::get('/order-detail/{code}', [OrderController::class, 'getOrderDetail'])->name('order.getOrderDetail');
    Route::get('/order/{status?}', [OrderController::class, 'getOrder'])->name('order.getOrder');
    Route::patch('/order-cancelation/{id}', [OrderController::class, 'canceledOrder']);
    Route::post('/orders/filter', [FilterController::class, 'filterOrdersByDate'])->middleware('auth');
    Route::get('/orders/filter-by-price', [FilterController::class, 'filterOrdersByPrice'])->name('orders.filterByPrice');


    // checkout
    Route::post('/checkout', [PaymentController::class, 'processPayment']);
    // vnpay
    Route::post('/vnpay/callback', [PaymentController::class, 'callback'])->name('callback');
    Route::post('/checkout-vnpay', [PaymentController::class, 'handleOrder']);

    //momo
    Route::post('/momo/callback', [PaymentController::class, 'callbackMomo'])->name('payment.momo.callback');
    Route::post('/checkout-momo', [PaymentController::class, 'handleOrder']);


    //Coupon_user
    Route::apiResource('coupon-user', CouponUserController::class);
    Route::patch('coupon-user/{id}', [CouponUserController::class, 'update']);

    //Counpon_cart
    Route::post('/coupon-cart', [HomeController::class, 'getCouponCart']);

    //Event_user
    Route::post('/spin', [SpinController::class, 'spin']);
    Route::post('/reset-daily-spins', [SpinController::class, 'resetDailySpins']);
    Route::post('/claim-coupon/{eventId}/{couponId}', [CouponUserController::class, 'claimCoupon']);
    Route::get('/event-coupons', [OderCheckController::class, 'getEventCoupons']);
    //quản lý lịch sử quay của người dùng
    Route::get('spin-history', [EventSpinController::class, 'getSpinHistoryByEvent']);
    //lịch sử quay của người dùng

    Route::get('user-spin-history', [EventSpinController::class, 'getUserSpinHistoryByEvent']);
    //lịch sử quay của người dùng

    Route::post('spinEvent/events', [EventSpinController::class, 'spinEvent']);

    //Review client
    Route::post('ratings/review', [ReviewController::class, 'review'])->name('ratings.review');
    Route::get('/orders/pending-reviews', [ReviewController::class, 'getPendingReviews'])->name('orders.pendingReviews');
    Route::get('/orders/detail-reviews/{code}', [ReviewController::class, 'detailReview'])->name('orders.detailReview');
    Route::get('reviews/reviewed-orders', [ReviewController::class, 'getReviewedOrders'])->name('reviews.getReviewedOrders');

    //changePassword
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    //update user information
    Route::put('/user/update', [AuthController::class, 'updateProfile']);
    //user_profile
    Route::get('/user/profile', [AuthController::class, 'getProfile']);

    //xác nhận giao hàng
    Route::post('/orders/{code}/confirm-delivered', [OrderController::class, 'confirmDelivered'])->name('orders.confirmDelivered');
});
