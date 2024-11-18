<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h1>Xin chào {{ $user->name }},</h1>
    <p>Mã xác nhận của bạn là: <strong>{{ $otp }}</strong></p>
    <p>Vui lòng sử dụng mã này để đặt lại mật khẩu của bạn.</p>
</body>
</html>
