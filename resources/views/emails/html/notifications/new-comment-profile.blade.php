@extends('emails.html.notifications._layout')

@section('content')
    <p class="text-body" style="margin: 0; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; color: #2f353e;">
        {{ $creatorName }} left a comment on your Pony.fm profile!
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 24px;">
        <tr>
            <td class="quote-block" style="background-color: #f7f1f8; border-left: 4px solid #b885bd; border-radius: 0 8px 8px 0; padding: 16px 20px;">
                <p class="text-muted" style="margin: 0 0 8px; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; line-height: 18px; color: #5d646f;">{{ $creatorName }} wrote:</p>
                <p class="text-body" style="margin: 0; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; color: #2f353e;">{{ $comment }}</p>
            </td>
        </tr>
    </table>

    @include('emails.html.notifications._button', [
        'url' => $notificationUrl,
        'label' => "Reply to {$creatorName}",
    ])
@endsection
