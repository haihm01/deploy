@component('mail::message')
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $message->embed(public_path('img/logo-web-header.png')) }}" alt="Logo" style="width: 150px;">
</div>

# Đặt lại mật khẩu

Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn: **{{ $email }}**.

@component('mail::button', ['url' => $url])
Đặt lại mật khẩu
@endcomponent

Nếu bạn không yêu cầu đặt lại mật khẩu, không cần thực hiện thêm hành động nào.

Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!
@endcomponent
