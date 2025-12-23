<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Email</title>
</head>
<body>
    <h2>Verifikasi Email Anda</h2>

    <p>Terima kasih telah mendaftar.</p>
    <p>Silakan klik tombol di bawah ini untuk memverifikasi email Anda:</p>

    <a href="{{ url('/api/user/verify-email/' . $token) }}"
        style="display:inline-block;padding:10px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;">
        Verifikasi Email
    </a>

    <p>Link berlaku selama 60 menit.</p>
</body>
</html>
