@extends('emails.plaintext.notifications._layout')

@section('content')
Congrats! {{ $creatorName }} is now following you on Pony.fm!

Here's a link to their profile:
{{ $notificationUrl }}
@endsection
