<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Akun</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f7fb; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg, #0f766e, #155e75); padding:24px 32px; text-align:center;">
                            <h1 style="margin:0; font-size:24px; line-height:32px; color:#ffffff;">Reset Password Akun</h1>
                            <p style="margin:8px 0 0; font-size:14px; line-height:20px; color:#d1fae5;">POC - Jamkrida Jakarta</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 16px; font-size:20px; line-height:28px; color:#111827;">Halo, {{ $userName }}!</h2>

                            <p style="margin:0 0 16px; font-size:15px; line-height:24px; color:#4b5563;">
                                Silakan klik tombol di bawah ini untuk reset password akun Anda.
                            </p>

                            <div style="margin:24px 0; text-align:center;">
                                <a href="{{ $resetPasswordUrl }}" style="display:inline-block; padding:14px 24px; color:#ffffff; background:linear-gradient(135deg, #0f766e, #155e75); text-decoration:none; border-radius:10px; font-weight:700;">
                                    Reset Password
                                </a>
                            </div>

                            <div style="margin:24px 0; padding:18px; border-radius:12px; background-color:#f0fdfa; border:1px dashed #14b8a6;">
                                <p style="margin:0 0 8px; font-size:13px; line-height:20px; letter-spacing:1px; text-transform:uppercase; color:#0f766e;">
                                    Link Reset Password
                                </p>
                                <p style="margin:0; font-size:14px; line-height:24px; color:#0f172a; word-break:break-all;">
                                    {{ $resetPasswordUrl }}
                                </p>
                            </div>

                            <div style="margin:24px 0; padding:16px; border-radius:12px; background-color:#fff7ed; border:1px solid #fdba74;">
                                <p style="margin:0; font-size:14px; line-height:22px; color:#9a3412;">
                                    Link ini berlaku selama 24 jam. Jika Anda tidak meminta reset password, abaikan email ini.
                                </p>
                            </div>

                            <p style="margin:0; font-size:15px; line-height:24px; color:#4b5563;">
                                Salam hangat,
                            </p>
                            <p style="margin:4px 0 0; font-size:15px; line-height:24px; font-weight:700; color:#111827;">
                                Tim POC - Jamkrida Jakarta
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
