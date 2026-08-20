{{-- A bulletproof-enough email button: padded link in a solid-colour cell. --}}
<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 24px auto 0;">
    <tr>
        <td align="center" bgcolor="#84528a" style="border-radius: 8px;">
            <a href="{{ $url }}" target="_blank"
               style="display: inline-block; padding: 12px 28px; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 8px;">{{ $label }}</a>
        </td>
    </tr>
</table>
