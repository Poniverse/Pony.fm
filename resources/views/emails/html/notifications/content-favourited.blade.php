@extends('emails.html.notifications._layout')

@section('content')
    <p class="text-body" style="margin: 0; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; color: #2f353e;">
        {{ $creatorName }} favourited your {{ $resourceType }},
        <a href="{{ $notificationUrl }}" target="_blank" style="color: #84528a; font-weight: 700;">{{ $resourceTitle }}</a>! Yay!
    </p>

    @include('emails.html.notifications._media', [
        'mediaUrl' => $creatorUrl,
        'mediaImage' => $thumbnailUrl,
        'mediaImageAlt' => "{$creatorName}'s avatar",
        'mediaTitle' => $creatorName,
        'mediaSubtitle' => $creatorBio ?: null,
        'mediaRound' => true,
    ])

    @include('emails.html.notifications._button', [
        'url' => $creatorUrl,
        'label' => "Visit {$creatorName}'s profile",
    ])
@endsection
