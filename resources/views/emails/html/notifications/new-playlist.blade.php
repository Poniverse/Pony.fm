@extends('emails.html.notifications._layout')

@section('content')
    <p class="text-body" style="margin: 0; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; color: #2f353e;">
        {{ $creatorName }} created a new playlist on Pony.fm!
    </p>

    @include('emails.html.notifications._media', [
        'mediaUrl' => $notificationUrl,
        'mediaImage' => $thumbnailUrl,
        'mediaImageAlt' => "Cover art for {$playlistTitle}",
        'mediaTitle' => $playlistTitle,
        'mediaSubtitle' => "by {$creatorName}",
    ])

    @include('emails.html.notifications._button', [
        'url' => $notificationUrl,
        'label' => 'Check it out',
    ])
@endsection
