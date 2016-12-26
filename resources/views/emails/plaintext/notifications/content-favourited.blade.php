@extends('emails.plaintext.notifications._layout')

@section('content')
{{ $creatorName }} favourited your {{ $resourceType }}, "{{ $resourceTitle }}". Yay!

Here's a link to the {{ $resourceType }}:
{{ $notificationUrl }}
@endsection
