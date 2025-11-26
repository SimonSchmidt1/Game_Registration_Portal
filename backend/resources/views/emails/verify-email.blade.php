<!DOCTYPE html>
<html lang="sk" style="margin:0;padding:0;">
<head>
  <meta charset="UTF-8" />
  <title>Overenie e-mailu</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fa;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table width="560" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.08);">
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 12px;font-size:22px;color:#222;font-weight:700;">Ahoj {{ $user->name }}!</h1>
              <p style="margin:0 0 18px;font-size:15px;line-height:22px;color:#444;">Ďakujeme za registráciu v <strong>Game Registration Portal</strong>. Prosím, potvrď svoju e-mailovú adresu kliknutím na tlačidlo nižšie.</p>
              <!-- Simple Button (works across all email clients) -->
              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                <tr>
                  <td align="center" style="padding-bottom: 24px;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <table border="0" cellspacing="0" cellpadding="0" role="presentation">
        <tr>
           <td align="center" bgcolor="#3490dc" style="border-radius: 6px;">
              <a href="{{ $verificationUrl }}" target="_blank" style="font-size: 16px; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; text-decoration: none; border-radius: 6px; padding: 16px 38px; border: 1px solid #3490dc; display: inline-block; font-weight: bold;">
                 ✓ OVERIŤ E-MAIL
              </a>
           </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 12px;font-size:13px;color:#666;">Ak by sa tlačidlo nezobrazovalo alebo nefungovalo, skopíruj tento odkaz do prehliadača:</p>
              <p style="margin:0 0 20px;font-size:12px;color:#336699;word-break:break-all;">
                <a href="{{ $verificationUrl }}" target="_blank" style="color:#336699;text-decoration:underline;">{{ $verificationUrl }}</a>
              </p>
              <p style="margin:0 0 24px;font-size:12px;color:#888;">Ak si účet nevytvoril ty, tento e-mail ignoruj.</p>
              <p style="margin:0;font-size:12px;color:#999;">S pozdravom,<br><strong>Tím Game Registration Portal</strong></p>
            </td>
          </tr>
        </table>
        <p style="margin:24px 0 0;font-size:11px;color:#999;">© {{ date('Y') }} Game Registration Portal</p>
      </td>
    </tr>
  </table>
</body>
</html>
