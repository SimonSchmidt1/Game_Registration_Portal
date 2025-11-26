<!DOCTYPE html>
<html lang="sk" style="margin:0;padding:0;">
<head>
  <meta charset="UTF-8" />
  <title>Reset hesla</title>
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
                Dostali sme požiadavku na reset hesla pre tvoj účet v <strong>Game Registration Portal</strong>.
              </p>
              <p style="margin:0 0 18px;font-size:15px;line-height:22px;color:#444;">
                Ak si to nebol ty, tento e-mail ignoruj. Heslo sa nezmení, pokiaľ neklikneš na tlačidlo nižšie a nenastavíš nové.
              </p>
              
              <!-- Reset Button -->
              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                <tr>
                  <td align="center" style="padding-bottom: 24px;">
                    <table border="0" cellspacing="0" cellpadding="0" role="presentation">
                      <tr>
                        <td align="center" bgcolor="#28a745" style="border-radius: 6px;">
                          <a href="{{ $resetUrl }}" target="_blank" style="font-size: 16px; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; border-radius: 6px; padding: 16px 38px; border: 1px solid #28a745; display: inline-block; font-weight: bold;">
                            🔑 RESETOVAŤ HESLO
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <div style="background:#d1ecf1;border-left:4px solid #17a2b8;padding:12px 16px;margin:0 0 20px;border-radius:4px;">
                <p style="margin:0;font-size:13px;color:#0c5460;font-weight:600;">ℹ️ Dôležité informácie:</p>
                <ul style="margin:8px 0 0;padding-left:20px;font-size:13px;color:#0c5460;">
                  <li>Tento odkaz je platný iba <strong>1 hodinu</strong></li>
                  <li>Môže byť použitý iba <strong>raz</strong></li>
                  <li>Po kliknutí budeš presmerovaný na stránku pre nastavenie nového hesla</li>
                </ul>
              </div>

              <p style="margin:0 0 12px;font-size:13px;color:#666;">Ak by sa tlačidlo nezobrazovalo, skopíruj tento odkaz:</p>
              <p style="margin:0 0 20px;font-size:12px;color:#336699;word-break:break-all;">
                <a href="{{ $resetUrl }}" target="_blank" style="color:#336699;text-decoration:underline;">{{ $resetUrl }}</a>
              </p>

              <p style="margin:0 0 8px;font-size:13px;color:#d9534f;font-weight:600;">🛡️ Bezpečnostné tipy:</p>
              <ul style="margin:0 0 24px;padding-left:20px;font-size:12px;color:#666;line-height:18px;">
                <li>Ak si nepožiadal o reset hesla, tento e-mail ignoruj</li>
                <li>Tvoje heslo zostane nezmenené, pokiaľ neklikneš na tlačidlo vyššie</li>
                <li>Nepoužívaj rovnaké heslo na viacerých stránkach</li>
                <li>Tento e-mail nikomu nepreposielaj</li>
              </ul>

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
