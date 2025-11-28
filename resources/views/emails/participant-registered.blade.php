<!DOCTYPE html>
<html>
<head>
    <title>Pendaftaran Berhasil</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #3b82f6; text-align: center;">Pendaftaran Berhasil!</h2>
        <p>Halo <strong>{{ $participant->name }}</strong>,</p>
        <p>Terima kasih telah mendaftar pada <strong>Lomba Inovasi Kabupaten Nganjuk</strong>. Data pendaftaran Anda telah kami terima dengan detail sebagai berikut:</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Nama Peserta</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $participant->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Instansi/Lembaga</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $participant->institution }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Kategori</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $participant->category->name }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Judul Inovasi</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $participant->innovation_title }}</td>
            </tr>
        </table>

        <p>Tim kami akan segera memverifikasi data dan dokumen Anda. Informasi selanjutnya mengenai tahapan lomba akan kami kirimkan melalui email ini atau dapat Anda pantau melalui website resmi.</p>
        
        <p>Jika ada pertanyaan, silakan hubungi narahubung kami.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 12px; color: #777;">
            <p>&copy; {{ date('Y') }} Bappeda Kabupaten Nganjuk. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
