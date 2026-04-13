<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f7fb; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg, #1d4ed8, #1e40af); padding:24px 32px; text-align:center;">
                            <h1 style="margin:0; font-size:24px; line-height:32px; color:#ffffff;">Verifikasi Akun</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;">Halo {{ $userName ?? 'Pengguna' }},</p>
                            <p style="margin:0 0 16px;">Silakan klik tombol di bawah ini untuk membuat password dan mengaktifkan akun Anda.</p>
                            <div style="margin:24px 0; text-align:center;">
                                <a href="{{ $verificationUrl }}" style="display:inline-block; padding:14px 24px; color:#ffffff; background:linear-gradient(135deg, #1d4ed8, #1e40af); text-decoration:none; border-radius:10px; font-weight:700;">Verifikasi Akun</a>
                            </div>
                            <p style="word-break:break-all;">{{ $verificationUrl }}</p>
                            <p style="margin:0;">Terima kasih,<br><strong>Tim POC - Jamkrida Jakarta</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
