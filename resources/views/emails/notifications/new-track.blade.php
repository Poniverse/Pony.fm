@extends('emails.notifications._layout')

@section('content')
<p>{{ $creatorName }} published a new track on Pony.fm! Listen to it now:</p>

<p><a href="{{ $notificationUrl }}" target="_blank">{{ $trackTitle }}</a></p>

@endsection
