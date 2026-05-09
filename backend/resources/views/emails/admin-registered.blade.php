<!DOCTYPE html>
<html lang="sk" style="margin:0;padding:0;">
<head>
  <meta charset="UTF-8" />
  <title>Váš účet bol vytvorený</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table width="560" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.08);">
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 12px;font-size:22px;color:#222;font-weight:700;">Ahoj {{ $user->name }}!</h1>
              <p style="margin:0 0 18px;font-size:15px;line-height:22px;color:#444;">
                Administrátor ti vytvoril účet v <strong>Game Registration Portal</strong>. Môžeš sa prihlásiť pomocou nasledujúcich údajov:
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8f9fa;border-radius:6px;margin-bottom:24px;">
                <tr>
                  <td style="padding:16px 20px;">
                    <p style="margin:0 0 8px;font-size:14px;color:#666;">
                      <strong style="color:#444;">E-mail:</strong>&nbsp; {{ $user->email }}
                    </p>
                    <p style="margin:0;font-size:14px;color:#666;">
                      <strong style="color:#444;">Heslo:</strong>&nbsp; {{ $plainPassword }}
                    </p>
                  </td>
                </tr>
              </table>

              <table border="0" cellspacing="0" cellpadding="0" role="presentation" style="margin-bottom:24px;">
                <tr>
                  <td align="center" bgcolor="#3490dc" style="border-radius:6px;">
                    <a href="{{ $loginUrl }}" target="_blank"
                       style="font-size:16px;font-family:Arial,sans-serif;color:#ffffff;text-decoration:none;border-radius:6px;padding:16px 38px;border:1px solid #3490dc;display:inline-block;font-weight:bold;">
                      Prihlásiť sa
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 12px;font-size:13px;color:#666;">
                Ak by sa tlačidlo nezobrazovalo, skopíruj tento odkaz do prehliadača:
              </p>
              <p style="margin:0 0 20px;font-size:12px;color:#336699;word-break:break-all;">
                <a href="{{ $loginUrl }}" target="_blank" style="color:#336699;text-decoration:underline;">{{ $loginUrl }}</a>
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#fff8e1;border-left:4px solid #f59e0b;border-radius:4px;margin-bottom:24px;">
                <tr>
                  <td style="padding:12px 16px;">
                    <p style="margin:0 0 4px;font-size:13px;font-weight:bold;color:#92400e;">⚠ Bezpečnostné upozornenie</p>
                    <p style="margin:0;font-size:13px;color:#92400e;line-height:20px;">
                      Toto heslo nikomu nezdieľaj a neposielaj ďalej. Odporúčame ho zmeniť ihneď po prvom prihlásení v nastaveniach profilu.
                    </p>
                  </td>
                </tr>
              </table>

              <hr style="border:none;border-top:1px solid #eee;margin:0 0 20px;" />
              <p style="margin:0;font-size:12px;color:#999;line-height:18px;">
                S pozdravom,<br>
                <strong style="color:#666;">Tím Game Registration Portal</strong><br>
                Fakulta prírodných vied a informatiky UCM
              </p>
            </td>
          </tr>
        </table>
        <p style="margin:24px 0 0;font-size:11px;color:#999;">© {{ date('Y') }} Game Registration Portal</p>
      </td>
    </tr>
  </table>
</body>
</html>
