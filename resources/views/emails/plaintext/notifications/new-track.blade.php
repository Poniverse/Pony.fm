@extends('emails.plaintext.notifications._layout')

@section('content')
{{ $creatorName }} published a new track on Pony.fm!

Title: {{ $trackTitle }}

Listen to it:
{{ $notificationUrl }}
@endsection
