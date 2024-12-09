<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class StatisticalController extends Controller
{

    public function NgayThongKe()
    {
        try {
            $ranges = [
                [
                    'label' => '1 Ngày',
                    'value' => 'one_day',
                    'startDate' => now()->startOfDay()->format('d M Y'),
                    'endDate' => now()->endOfDay()->format('d M Y'),
                ],
                [
                    'label' => '1 Tuần',
                    'value' => 'one_week',
                    'startDate' => now()->startOfWeek()->format('d M Y'),
                    'endDate' => now()->endOfWeek()->format('d M Y'),
                ],
                [
                    'label' => '1 Tháng',
                    'value' => 'one_month',
                    'startDate' => now()->startOfMonth()->format('d M Y'),
                    'endDate' => now()->endOfMonth()->format('d M Y'),
                ],
                [
                    'label' => '6 Tháng',
                    'value' => 'six_months',
                    'startDate' => now()->subMonths(6)->startOfMonth()->format('d M Y'),
                    'endDate' => now()->format('d M Y'),
                ],
                [
                    'label' => '1 Năm',
                    'value' => 'one_year',
                    'startDate' => Carbon::createFromDate(2024, 1, 1)->startOfDay()->format('d M Y'),
                    'endDate' => now()->format('d M Y'),
                ],
                [
                    'label' => 'Năm trước',
                    'value' => 'last_year',
                    'startDate' => now()->subYear()->startOfYear()->format('d M Y'),
                    'endDate' => now()->subYear()->endOfYear()->format('d M Y'),
                ],
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'startDate' => Order::min('created_at')
                        ? Carbon::parse(Order::min('created_at'))->format('d M Y')
                        : now()->format('d M Y'),
                    'endDate' => now()->addDay()->format('d M Y'),
                ],
            ];
    
            return response()->json($ranges);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function doanhThu(Request $request)
    {
        try {
            $type = $request->type;

            $query = Order::where('status_order', 'delivered');

            match ($type) {
                'day' => $query->whereDate('created_at', today()),
                'month' => $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year),
                '6months' => $query->whereBetween('created_at', [
                    now()->subMonths(6)->startOfMonth(),
                    now()->endOfMonth()
                ]),
                'year' => $query->whereYear('created_at', now()->year),
                'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->year),
                default => $query,
            };

            $totalRevenue = $query->sum('final_total');

            $result = $query->get(['created_at', 'final_total'])
                ->groupBy(function ($order) {
                    return Carbon::parse($order->created_at)
                        ->timezone('Asia/Ho_Chi_Minh')
                        ->startOfDay()
                        ->timestamp * 1000;
                })
                ->sortKeys()
                ->map(function ($group, $timestamp) {
                    return [$timestamp, $group->sum('final_total')];
                })
                ->values();

            return response()->json([
                'data' => $result,
                'total_revenue' => (float) $totalRevenue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function thongKeSanPham(Request $request)
    {
        try {
            $type = $request->type ?? 'all';

            // Kiểm tra giá trị 'type' có hợp lệ
            if (!in_array($type, ['all', 'day', 'month', '6months', 'year', 'last_month'])) {
                return response()->json([
                    'error' => 'Tham số type không hợp lệ. Chỉ chấp nhận: all, day, month, 6months, year, last_month.',
                ], 400);
            }

            $totalProducts = Product::count();

            // Lấy điều kiện thời gian dựa trên tham số 'type'
            $dateCondition = $this->getDateCondition($type);

            // Thống kê sản phẩm bán chạy nhất
            $topSellingProducts = Product::with([
                'variants' => function ($query) use ($dateCondition) {
                    $query->withSum([
                        'orderItems as total_revenue' => function ($query) use ($dateCondition) {
                            $query->select(DB::raw('SUM(order_items.total_price)'))
                                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                                ->where('orders.status_order', Order::STATUS_ORDER_DELIVERED);
                            if ($dateCondition) {
                                $dateCondition($query, 'orders.created_at');
                            }
                        }
                    ], 'total_price');
                },
                'category:id,name',
                'variants.attributeValues'

            ])
                ->get()
                ->map(function ($product) {
                    $product->total_revenue = $product->variants->sum('total_revenue') ?? 0;
                    $product->images = json_decode($product->images, true);
                    return $product;
                })
                ->sortByDesc('total_revenue')
                ->take(10)
                ->values();


            // Thống kê sản phẩm theo đánh giá cao nhất
            $topRatedProductsQuery = Product::query()
                ->withAvg('rates', 'rating')  // Lấy điểm trung bình
                ->withCount('rates')  // Lấy số lượng đánh giá
                ->having('rates_avg_rating', '>', 0)
                ->with('category:id,name');

            if ($dateCondition) {
                $topRatedProductsQuery->whereHas('rates', function ($query) use ($dateCondition) {
                    $dateCondition($query, 'rates.created_at');
                });
            }

            $topRatedProducts = $topRatedProductsQuery
                ->orderByDesc('rates_avg_rating')
                ->take(10)
                ->get()
                ->map(function ($product) {
                    // Làm tròn giá trị của 'rates_avg_rating' tới 0.5
                    $product->rates_avg_rating = round($product->rates_avg_rating * 2) / 2;
                    $product->images = json_decode($product->images, true);
                    return $product;
                });

            // Thống kê sản phẩm theo đánh giá thấp nhất
            $lowestRatedProductsQuery = Product::query()
                ->withAvg('rates', 'rating')  // Lấy điểm trung bình
                ->withCount('rates')  // Lấy số lượng đánh giá
                ->having('rates_avg_rating', '>', 0)
                ->with('category:id,name');

            if ($dateCondition) {
                $lowestRatedProductsQuery->whereHas('rates', function ($query) use ($dateCondition) {
                    $dateCondition($query, 'rates.created_at');
                });
            }

            $lowestRatedProducts = $lowestRatedProductsQuery
                ->orderBy('rates_avg_rating')
                ->take(10)
                ->get()
                ->map(function ($product) {
                    // Làm tròn giá trị của 'rates_avg_rating' tới 0.5
                    $product->rates_avg_rating = round($product->rates_avg_rating * 2) / 2;
                    $product->images = json_decode($product->images, true);
                    return $product;
                });


            // Trả về kết quả thống kê
            return response()->json([
                'total_products' => $totalProducts,
                'top_selling_products' => $topSellingProducts,
                'top_rated_products' => $topRatedProducts,
                'lowest_rated_products' => $lowestRatedProducts,
            ]);
        } catch (\Exception $e) {
            // Xử lý lỗi
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function getDateCondition($type)
    {
        switch ($type) {
            case 'day':
                return function ($query, $dateColumn) {
                    $query->whereDate($dateColumn, today());
                };
            case 'month':
                return function ($query, $dateColumn) {
                    $query->whereMonth($dateColumn, now()->month)
                        ->whereYear($dateColumn, now()->year);
                };
            case '6months':
                return function ($query, $dateColumn) {
                    $query->whereBetween($dateColumn, [
                        now()->subMonths(6)->startOfMonth(),
                        now()->endOfMonth()
                    ]);
                };
            case 'year':
                return function ($query, $dateColumn) {
                    $query->whereYear($dateColumn, now()->year);
                };
            case 'last_month':
                return function ($query, $dateColumn) {
                    $query->whereMonth($dateColumn, now()->subMonth()->month)
                        ->whereYear($dateColumn, now()->year);
                };
            default:
                return null;
        }
    }



    //thống kê đơn hangf
    public function thongKeDonHang(Request $request)
    {
        try {
            $type = $request->query('type', 'all'); // Loại thống kê: 'all', 'day', 'month', '6months', 'year', 'last_month'

            // Kiểm tra giá trị 'type' có hợp lệ
            if (!in_array($type, ['all', 'day', 'month', '6months', 'year', 'last_month'])) {
                return response()->json([
                    'error' => 'Tham số type không hợp lệ. Chỉ chấp nhận: all, day, month, 6months, year, last_month.',
                ], 400);
            }

            // Tạo query cơ bản để áp dụng điều kiện thời gian
            $baseQuery = Order::query();

            // Áp dụng điều kiện thời gian
            $baseQuery->when($type === 'day', fn($q) => $q->whereDate('created_at', now()))
                ->when($type === 'month', fn($q) => $q->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year))
                ->when($type === '6months', fn($q) => $q->whereBetween('created_at', [
                    now()->subMonths(6)->startOfDay(),
                    now()->endOfDay()
                ]))
                ->when($type === 'year', fn($q) => $q->whereYear('created_at', now()->year))
                ->when($type === 'last_month', fn($q) => $q->whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->subMonth()->year));

            // Tổng số đơn hàng
            $totalOrders = $baseQuery->count();

            // Đơn hàng đã giao
            $deliveredOrders = (clone $baseQuery)
                ->where('status_order', Order::STATUS_ORDER_DELIVERED)
                ->count();

            // Đơn hàng bị hủy
            $canceledOrders = (clone $baseQuery)
                ->where('status_order', Order::STATUS_ORDER_CANCELED)
                ->count();

            // Thống kê theo phương thức thanh toán
            $paymentMethods = $baseQuery->select('payment_method', DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get();

            return response()->json([
                'total_orders' => $totalOrders,
                'delivered_orders' => $deliveredOrders,
                'canceled_orders' => $canceledOrders,
                'payment_methods' => $paymentMethods,
            ]);
        } catch (\Exception $e) {
            // Xử lý lỗi
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    //thôgns kê lại doanh thu trongngay,đơn hàng mới,đơn hàng chưa xác nhận,và đếm số ng dùng
    public function thongKeNgay(Request $request)
    {
        try {
            $today = now()->startOfDay();
            $endOfDay = now()->endOfDay();

            $revenueToday = Order::where('status_order', 'delivered')
                ->whereBetween('updated_at', [$today, $endOfDay])
                ->sum('final_total');

            // Số đơn hàng mới trong ngày
            $newOrdersCount = Order::whereBetween('created_at', [$today, $endOfDay])->count();

            // Số đơn hàng chờ xác nhận
            $pendingOrdersCount = Order::where('status_order', 'pending')->count();

            $totalUsers = User::count();

            // Trả về kết quả thống kê
            return response()->json([
                'revenue_today' => $revenueToday,
                'new_orders_count' => $newOrdersCount,
                'pending_orders_count' => $pendingOrdersCount,
                'total_users' => $totalUsers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function listSoLuongBienTheDuoi10(): JsonResponse
    {
        try {
            $variants = InventoryStock::with(['variant.attributeValues', 'variant.product'])
                ->where('quantity', '<', 10)
                ->where('quantity', '>', 0)
                ->paginate(10);

            $variants->getCollection()->transform(function ($inventoryStock) {
                if (isset($inventoryStock->variant->product->images)) {
                    if (!is_array($inventoryStock->variant->product->images)) {
                        $inventoryStock->variant->product->images = json_decode($inventoryStock->variant->product->images, true);
                    }
                }
                return $inventoryStock;
            });

            return response()->json([
                'variants' => $variants,
                'count' => $variants->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi khi lấy danh sách biến thể có số lượng dưới 10.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function listSoLuongBienTheDaHet(): JsonResponse
    {
        try {
            $variants = InventoryStock::with(['variant.attributeValues', 'variant.product'])
                ->where('quantity', '=', 0)
                ->paginate(10);

            $variants->getCollection()->transform(function ($inventoryStock) {
                if (isset($inventoryStock->variant->product->images)) {
                    if (!is_array($inventoryStock->variant->product->images)) {
                        $inventoryStock->variant->product->images = json_decode($inventoryStock->variant->product->images, true);
                    }
                }
                return $inventoryStock;
            });

            return response()->json([
                'variants' => $variants,
                'count' => $variants->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi khi lấy danh sách biến thể đã hết hàng.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function profit(Request $request)
    {
        try {
            $type = $request->type;

            $query = Order::where('status_order', 'delivered');

            match ($type) {
                'day' => $query->whereDate('created_at', today()),
                'month' => $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year),
                '6months' => $query->whereBetween('created_at', [
                    now()->subMonths(6)->startOfMonth(),
                    now()->endOfMonth()
                ]),
                'year' => $query->whereYear('created_at', now()->year),
                'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->year),
                default => $query,
            };

            $totalRevenue = $query->sum('profit');

            $result = $query->get(['created_at', 'profit'])
                ->groupBy(function ($order) {
                    return Carbon::parse($order->created_at)
                        ->timezone('Asia/Ho_Chi_Minh')
                        ->startOfDay()
                        ->timestamp * 1000;
                })
                ->sortKeys()
                ->map(function ($group, $timestamp) {
                    return [$timestamp, $group->sum('profit')];
                })
                ->values();

            return response()->json([
                'data' => $result,
                'total_revenue' => (float) $totalRevenue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

}
