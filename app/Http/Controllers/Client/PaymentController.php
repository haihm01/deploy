<?php

namespace App\Http\Controllers\Client;

use App\Events\OrderShipped;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\Order;
use App\Models\OrderCoupon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private $tmnCode = "KFDM60UR";
    private $secretKey = "TSM1WWZ64ZAR2KYEEHAE99OWBYBCW9VQ";
    private $vnpUrl = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
    private $returnUrl = "http://localhost:5173/check-out";


    // Cấu hình MoMo
    private $momoEndpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
    private $momoPartnerCode = "MOMOBKUN20180529";
    private $momoAccessKey = "klm05TvNBzhg7h7j";
    private $momoSecretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";
    private $momoReturnUrl = "http://localhost:5173/check-out";

    public function processPayment(Request $request)
    {
        $paymentMethod = $request->payment_method; // Nhận phương thức thanh toán từ form

        switch ($paymentMethod) {
            case 'COD':
                return $this->handleOrder($request);
            case 'VNPAY':
                return $this->handleVNPay($request);
            case 'MOMO':
                return $this->handleMoMo($request);
            default:
                return response()->json(['error' => 'Phương thức thanh toán không hợp lệ'], 400);
        }
    }

    function handleVNPay(Request $request)
    {
        $ipAddr = request()->ip();
        $createDate = Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis');
        $currCode = "VND";
        $vnpParams = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Locale' => 'vn',
            'vnp_CurrCode' => $currCode,
            'vnp_TxnRef' => Str::random(10),
            'vnp_OrderInfo' => 'orderDescription 12345646',
            'vnp_OrderType' => 'other',
            'vnp_Amount' => $request->final_total * 100,
            'vnp_ReturnUrl' => $this->returnUrl,
            'vnp_IpAddr' => $ipAddr,
            'vnp_CreateDate' => $createDate,
        ];

        ksort($vnpParams);
        $query = "";
        $hashdata = "";
        $i = 0;
        foreach ($vnpParams as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $query = rtrim($query, '&'); // Remove trailing '&' from query string
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $this->secretKey);
        $vnp_Url = $this->vnpUrl . "?" . $query . '&vnp_SecureHash=' . $vnpSecureHash;

        return response()->json(['paymentUrl' => $vnp_Url]);
    }


    public function handleOrder(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
    
                $selectedItems = $request->selected_items;
    
                // Nếu không có sản phẩm nào được chọn
                if (empty($selectedItems)) {
                    return response()->json([
                        'error' => 'Bạn chưa chọn sản phẩm nào để thanh toán.'
                    ]);
                }
    
                $cartItems = Cart::query()
                    ->with(['variant.attributeValues.attribute', 'variant.product', 'user', 'variant.inventoryStock'])
                    ->where('user_id', Auth::id())
                    ->whereIn('id', $selectedItems)
                    ->get();
    
                if ($cartItems->isEmpty()) {
                    return response()->json(['error' => 'Không tìm thấy sản phẩm nào trong giỏ hàng.']);
                }
    
                $data = $request->all();
                $data['user_id'] = Auth::id();
                $data['code'] = $this->generateOrderCode();
                $data['grand_total'] = 0;
    
                // Kiểm tra số lượng tồn kho trước khi tính tổng giá trị đơn hàng
                foreach ($cartItems as $value) {
                    $requiredQuantity = $value->quantity;
    
                    // Kiểm tra số lượng trong kho
                    $stockQuantity = $value->variant->inventoryStock->quantity;
                    if ($requiredQuantity > $stockQuantity) {
                        return response()->json([
                            'error' => 'Số lượng sản phẩm ' . $value->variant->product->name . ' không đủ trong kho. Còn ' . $stockQuantity . ' sản phẩm.'
                        ]);
                    }
    
                    // Cộng dồn tổng giá trị đơn hàng
                    $data['grand_total'] += $requiredQuantity * ($value->variant->sale_price ?: $value->variant->price);
                }
    
                if ($request->payment_method === "VNPAY" || $request->payment_method === "MOMO") {
                    $data['paid_at'] = now();
                }
    
                if ($request->final_total < 0) {
                    $data['final_total'] = 0;
                }
    
                // Tạo đơn hàng
                $order = Order::query()->create($data);
    
                if ($request->coupon_id && $request->discount_amount) {
                    $coupon = Coupon::query()->where('id', $request->coupon_id)->first();
                    $coupon->usage_limit -= 1;
                    $coupon->save();
    
                    OrderCoupon::query()->create([
                        'order_id' => $order->id,
                        'discount_amount' => $request->discount_amount,
                        'coupon_id' => $request->coupon_id
                    ]);
    
                    CouponUser::create([
                        'user_id' => Auth::id(),
                        'coupon_id' => $request->coupon_id,
                        'used_at' => now(),
                    ]);
                }
    
                if (!$order) {
                    return response()->json(['error' => 'Đặt hàng không thành công.']);
                }
    
                // Gửi mail && Xóa cart
                $order['idCart'] = $selectedItems;
                $order['discount_amount'] = $request->discount_amount;
                $order['items'] = $cartItems;
                $order['user'] = Auth::user();
                $orderData = json_decode($order);
                OrderShipped::dispatch($orderData);
    
                return response()->json([
                    'message' => 'ĐẶT HÀNG THÀNH CÔNG',
                    'description' => 'Xin cảm ơn Quý khách đã tin tưởng và mua sắm tại cửa hàng của chúng tôi.'
                ]);
            });
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . '@' . __FUNCTION__, [
                'line' => $th->getLine(),
                'message' => $th->getMessage()
            ]);
    
            return response()->json([
                'message' => 'Lỗi tải trang',
                'status' => 'error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    public function callback(Request $request)
    {
        Log::info("Callback request data: " . json_encode($request->all()));
        $vnp_SecureHash = $request->vnp_SecureHash;

        $inputData = array();
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;

        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }


        $secureHash = hash_hmac('sha512', $hashData, $this->secretKey);

        if ($secureHash == $vnp_SecureHash) {

            if ($request->vnp_ResponseCode == '00') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Giao dịch thành công',
                ]);

            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Giao dịch không thành công',
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Tài khoản không đúng. Giao dịch không thành công',
            ]);
        }
    }



    public function handleMoMo(Request $request)
    {
        try {
            $orderId = time() . "";
            $requestId = time() . "";
            $amount = $request->final_total;
            $orderInfo = "Thanh toán đơn hàng MoMo";
            $extraData = "";

            // Tạo chuỗi hash đúng thứ tự
            $rawHash = "accessKey=" . $this->momoAccessKey;
            $rawHash .= "&amount=" . $amount;
            $rawHash .= "&extraData=" . $extraData;
            $rawHash .= "&ipnUrl=" . $this->momoReturnUrl;
            $rawHash .= "&orderId=" . $orderId;
            $rawHash .= "&orderInfo=" . $orderInfo;
            $rawHash .= "&partnerCode=" . $this->momoPartnerCode;
            $rawHash .= "&redirectUrl=" . $this->momoReturnUrl;
            $rawHash .= "&requestId=" . $requestId;
            $rawHash .= "&requestType=payWithMethod";

            // Bước 2: Tạo chữ ký
            $signature = hash_hmac("sha256", $rawHash, $this->momoSecretKey);

            $params = [
                'partnerCode' => $this->momoPartnerCode,
                'accessKey' => $this->momoAccessKey,
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $this->momoReturnUrl,
                'ipnUrl' => $this->momoReturnUrl,
                'extraData' => $extraData,
                'requestType' => 'payWithMethod',
                'signature' => $signature,
                'lang' => 'vi'  // Thêm ngôn ngữ
            ];

            // Log request params để debug
            Log::info("MoMo Payment Request Params: ", $params);
            Log::info("MoMo Raw Hash: " . $rawHash);
            Log::info("MoMo Signature: " . $signature);

            // Bước 4: Gửi request đến MoMo
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->momoEndpoint);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            $result = curl_exec($ch);

            // Kiểm tra lỗi curl
            if (curl_errno($ch)) {
                Log::error('Curl error: ' . curl_error($ch));
                return response()->json(['error' => 'Lỗi kết nối đến MoMo'], 500);
            }
            curl_close($ch);

            // Log response để debug
            Log::info("MoMo Payment Response: " . $result);

            // Bước 5: Xử lý response
            $jsonResult = json_decode($result, true);

            if (isset($jsonResult['payUrl'])) {
                return response()->json([
                    'paymentUrl' => $jsonResult['payUrl']
                ]);
            } else {
                Log::error("MoMo Payment Error: ", $jsonResult);
                return response()->json([
                    'status' => 'error',
                    'message' => $jsonResult['localMessage'] ?? 'Có lỗi xảy ra khi tạo yêu cầu thanh toán MoMo'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("MoMo Payment Exception: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Có lỗi xảy ra trong quá trình xử lý'
            ], 500);
        }
    }

    public function callbackMomo(Request $request)
    {
        // Log the incoming request data
        Log::info("MoMo callback request data: " . json_encode($request->all()));
        
        // Get the signature from request
        $requestSignature = $request->signature;
    
        // Prepare input data for signature verification
        $inputData = [
            'accessKey' => $this->momoAccessKey,
            'amount' => $request->amount,
            'extraData' => $request->extraData,
            'message' => $request->message,
            'orderId' => $request->orderId,
            'orderInfo' => $request->orderInfo,
            'orderType' => $request->orderType,
            'partnerCode' => $request->partnerCode,
            'payType' => $request->payType,
            'requestId' => $request->requestId,
            'responseTime' => $request->responseTime,
            'resultCode' => $request->resultCode,
            'transId' => $request->transId
        ];
    
        // Create raw hash string
        $rawHash = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 0) {
                $rawHash = $rawHash . $key . "=" . $value;
            } else {
                $rawHash = $rawHash . "&" . $key . "=" . $value;
            }
            $i++;
        }
    
        // Generate signature for verification
        $signature = hash_hmac("sha256", $rawHash, $this->momoSecretKey);
    
        // Verify signature and process payment
        if ($signature == $requestSignature) {
            if ($request->resultCode == '0') {
                // Payment successful
                try {
                    // Update your order status in database here if needed
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Giao dịch thành công'
                    ]);
                } catch (\Exception $e) {
                    Log::error("MoMo callback error: " . $e->getMessage());
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Lỗi xử lý giao dịch'
                    ]);
                }
            } else {
                // Payment failed
                return response()->json([
                    'status' => 'error',
                    'message' => 'Giao dịch không thành công'
                ]);
            }
        } else {
            // Invalid signature
            return response()->json([
                'status' => 'error',
                'message' => 'Chữ ký không hợp lệ. Giao dịch không thành công'
            ]);
        }
    }
    private function generateOrderCode()
    {
        $date = date('Ymd');
        $lastOrder = Order::whereDate('created_at', today())->orderBy('code', 'desc')->first();

        if ($lastOrder && preg_match('/ORD-' . $date . '-(\d{3})$/', $lastOrder->code, $matches)) {
            $increment = intval($matches[1]) + 1;
        } else {
            $increment = 1;
        }

        return 'ORD-' . $date . '-' . str_pad($increment, 3, '0', STR_PAD_LEFT);
    }

}