<!DOCTYPE html>
<html>
<head>
    <title>Akun Juri Lomba Inovasi</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #3b82f6; text-align: center;">Selamat Datang di Tim Penilai!</h2>
        <p>Halo <strong>{{ $user->name }}</strong>,</p>
        <p>Anda telah didaftarkan sebagai <strong>Juri</strong> pada Lomba Inovasi Kabupaten Nganjuk. Berikut adalah detail akun Anda untuk login ke dalam sistem penilaian:</p>
        
        <div style="background-color: #f9fafb; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>URL Login:</strong> <a href="{{ url('/admin/login') }}">{{ url('/admin/login') }}</a></p>
            <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email }}</p>
            <p style="margin: 5px 0;"><strong>Password:</strong> <span style="font-family: monospace; background-color: #e5e7eb; padding: 2px 5px; border-radius: 3px;">{{ $password }}</span></p>
        </div>

        <p>Mohon segera login dan mengganti password Anda demi keamanan akun.</p>
        
        <p>Terima kasih atas partisipasi Anda.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 12px; color: #777;">
            <p>&copy; {{ date('Y') }} Bappeda Kabupaten Nganjuk. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
