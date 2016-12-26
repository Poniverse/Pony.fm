@extends('emails.plaintext.notifications._layout')

@section('content')
{{ $creatorName }} created a new playlist on Pony.fm!

Title: {{ $playlistTitle }}

Listen to it:
{{ $notificationUrl }}
@endsection
