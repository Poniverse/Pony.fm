@extends('emails.html.notifications._layout')

@section('content')
    <p class="text-body" style="margin: 0; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 24px; color: #2f353e;">
        Congrats! {{ $creatorName }} is now following you on Pony.fm!
    </p>

    @include('emails.html.notifications._media', [
        'mediaUrl' => $notificationUrl,
        'mediaImage' => $thumbnailUrl,
        'mediaImageAlt' => "{$creatorName}'s avatar",
        'mediaTitle' => $creatorName,
        'mediaSubtitle' => $creatorBio ?: null,
        'mediaRound' => true,
    ])

    @include('emails.html.notifications._button', [
        'url' => $notificationUrl,
        'label' => "Visit {$creatorName}'s profile",
    ])
@endsection
