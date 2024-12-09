<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Báo Hủy Đơn Hàng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            margin: auto;
        }
        h1 {
            color: #333;
        }
        p {
            color: #555;
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .product-table th, .product-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .product-table th {
            background-color: #f4f4f4;
            text-align: left;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Thông Báo Hủy Đơn Hàng</h1>
        <p>Chào bạn,</p>
        <p>Chúng tôi rất tiếc phải thông báo rằng đơn hàng {{ $order->code }} của bạn đã bị hủy. Dưới đây là thông tin chi tiết về đơn hàng:</p>
        
        <table class="product-table">
            <thead>
                <tr>
                    <th>Tên Sản Phẩm</th>
                    <th>Số Lượng</th>
                    <th>Giá</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($variant as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price) }}</td>
                    </tr>
                @endforeach    
            </tbody>
        </table>
        <p><span style="color:red;">Lý do huỷ: {{ $order->reason }}</span></p>
        <p>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi. Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>

        <div class="footer">
            <p>Trân trọng,<br>Đội ngũ hỗ trợ khách hàng</p>
        </div>
    </div>
</body>
</html>