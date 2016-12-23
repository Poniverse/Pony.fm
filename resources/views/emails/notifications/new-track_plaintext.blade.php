@extends('emails.notifications._layout_plaintext')

@section('content')
{{ $creatorName }} published a new track on Pony.fm!

Title: {{ $trackTitle }}

Listen to it:
{{ $notificationUrl }}
@endsection
