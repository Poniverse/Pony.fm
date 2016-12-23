@extends('emails.notifications._layout_plaintext')

@section('content')
{{ $creatorName }} created a new playlist on Pony.fm!

Title: {{ $playlistTitle }}

Listen to it:
{{ $notificationUrl }}
@endsection
