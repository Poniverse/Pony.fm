{{-- Thumbnail + title/subtitle row. Pass: $mediaUrl, $mediaImage, $mediaImageAlt,
     $mediaTitle, $mediaSubtitle (optional), $mediaRound (true for avatars). --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 24px;">
    <tr>
        <td width="72" valign="top">
            <a href="{{ $mediaUrl }}" target="_blank">
                <img src="{{ $mediaImage }}" alt="{{ $mediaImageAlt }}" width="72" height="72"
                     style="display: block; width: 72px; height: 72px; object-fit: cover; border-radius: {{ ($mediaRound ?? false) ? '50%' : '8px' }}; background-color: #eaecf1;">
            </a>
        </td>
        <td width="16" style="font-size: 0; line-height: 0;">&nbsp;</td>
        <td valign="middle">
            <p style="margin: 0;">
                <a href="{{ $mediaUrl }}" target="_blank" class="text-heading"
                   style="font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 700; line-height: 24px; color: #14161b; text-decoration: none;">{{ $mediaTitle }}</a>
            </p>
            @isset($mediaSubtitle)
                <p class="text-muted" style="margin: 4px 0 0; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #5d646f;">{{ $mediaSubtitle }}</p>
            @endisset
        </td>
    </tr>
</table>
