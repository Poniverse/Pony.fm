@extends('emails.notifications._layout')

@section('content')
<p>{{ $creatorName }} created a new playlist on Pony.fm! Check it out:</p>

<p><a href="{{ $notificationUrl }}" target="_blank">{{ $playlistTitle }}</a></p>

@endsection
