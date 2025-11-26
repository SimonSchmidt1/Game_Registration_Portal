<!DOCTYPE html>
<html lang="sk" style="margin:0;padding:0;">
<head>
  <meta charset="UTF-8" />
  <title>Dočasné heslo</title>
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
                Zaznamenali sme viacero neúspešných pokusov o prihlásenie do tvojho účtu v <strong>Game Registration Portal</strong>.
              </p>
              <p style="margin:0 0 18px;font-size:15px;line-height:22px;color:#444;">
                Pre tvoje pohodlie sme vygenerovali <strong>dočasné heslo</strong>, ktoré môžeš použiť na prihlásenie:
              </p>
              
              <!-- Temporary Password Box -->
              <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation" style="margin-bottom:24px;">
                <tr>
                  <td align="center" style="background:#f8f9fa;border:2px dashed #6c757d;border-radius:8px;padding:20px;">
                    <p style="margin:0 0 8px;font-size:13px;color:#6c757d;font-weight:600;">DOČASNÉ HESLO</p>
                    <p style="margin:0;font-size:24px;color:#dc3545;font-weight:700;letter-spacing:2px;font-family:'Courier New',monospace;">
                      {{ $temporaryPassword }}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 18px;font-size:15px;line-height:22px;color:#444;">
                Toto heslo je platné <strong>15 minút</strong>. Po prihlásení ti <strong>odporúčame okamžite zmeniť heslo</strong> v nastaveniach profilu.
              </p>

              <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px 16px;margin:0 0 20px;border-radius:4px;">
                <p style="margin:0;font-size:13px;color:#856404;font-weight:600;">⚠️ Dôležité informácie:</p>
                <ul style="margin:8px 0 0;padding-left:20px;font-size:13px;color:#856404;">
                  <li>Dočasné heslo je platné iba <strong>15 minút</strong></li>
                  <li>Po úspešnom prihlásení zmeň heslo v profile</li>
                  <li>Môžeš sa prihlásiť na: <a href="{{ config('app.frontend_url') }}/login" style="color:#856404;">{{ config('app.frontend_url') }}/login</a></li>
                </ul>
              </div>

              <p style="margin:0 0 8px;font-size:13px;color:#d9534f;font-weight:600;">🛡️ Bezpečnostné tipy:</p>
              <ul style="margin:0 0 24px;padding-left:20px;font-size:12px;color:#666;line-height:18px;">
                <li>Ak si sa nepokúšal prihlásiť, kontaktuj administrátora</li>
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
