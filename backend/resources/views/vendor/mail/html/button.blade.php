@props([
        'url',
        'color' => 'primary',
        'align' => 'center',
])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:24px 0;">
    <tr>
        <td align="{{ $align }}">
            <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td align="center">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:48px;v-text-anchor:middle;width:260px;" arcsize="10%" stroke="f" fillcolor="#3490dc">
                            <w:anchorlock/>
                            <center style="color:#ffffff;font-family:Arial, sans-serif;font-size:16px;font-weight:bold;">
                                {!! $slot !!}
                            </center>
                        </v:roundrect>
                        <![endif]-->
                        <!--[if !mso]><!-- -->
                        <a href="{{ $url }}" target="_blank" rel="noopener" style="display:inline-block;background:#3490dc;border:2px solid #3490dc;border-radius:6px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:700;line-height:20px;text-decoration:none;padding:14px 32px;min-width:260px;text-align:center;mso-hide:all;">
                            {!! $slot !!}
                        </a>
                        <!--<![endif]-->
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<div style="font-size:12px;color:#666666;text-align:{{ $align }};line-height:18px;">Ak by sa tlačidlo nezobrazovalo, skopírujte tento odkaz: <br><span style="word-break:break-all;">{{ $url }}</span></div>
