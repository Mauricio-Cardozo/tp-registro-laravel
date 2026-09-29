<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; padding:32px; max-width:600px;">
                    <tr>
                        <td>
                            <h1 style="margin:0 0 16px; font-size:22px; color:#111827;">¡Hola, {{ $name }}!</h1>
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#374151;">
                                Tu cuenta en <strong>{{ config('app.name') }}</strong> fue creada exitosamente.
                                Ya podés iniciar sesión y empezar a usar la plataforma.
                            </p>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#374151;">
                                Si no fuiste vos, respondé este correo e ignorá el mensaje.
                            </p>
                            <p style="margin:0; font-size:13px; color:#6b7280;">
                                Saludos,<br>
                                El equipo de {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="font-size:12px; color:#9ca3af; padding-top:16px;">
                    Este mensaje fue generado automáticamente, no respondas a este correo.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
