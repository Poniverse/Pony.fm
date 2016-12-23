@extends('emails.notifications._layout_plaintext')

@section('content')
{{ $creatorName }} favourited your {{ $resourceType }}, "{{ $resourceTitle }}". Yay!

Here's a link to the {{ $resourceType }}:
{{ $notificationUrl }}
@endsection
