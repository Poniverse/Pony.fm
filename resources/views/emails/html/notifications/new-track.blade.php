@extends('emails.html.notifications._layout')

@section('content')
    <p class="text-body" style="margin: 0; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; color: #2f353e;">
        {{ $creatorName }} published a new track on Pony.fm!
    </p>

    @include('emails.html.notifications._media', [
        'mediaUrl' => $notificationUrl,
        'mediaImage' => $thumbnailUrl,
        'mediaImageAlt' => "Cover art for {$trackTitle}",
        'mediaTitle' => $trackTitle,
        'mediaSubtitle' => "by {$creatorName} · {$genreTitle}",
    ])

    @include('emails.html.notifications._button', [
        'url' => $notificationUrl,
        'label' => 'Listen to it now',
    ])
@endsection
