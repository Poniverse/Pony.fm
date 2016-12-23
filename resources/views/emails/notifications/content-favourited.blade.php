@extends('emails.notifications._layout')

@section('content')
<p>
    {{ $creatorName }} favourited your {{ $resourceType }},
    <em><a href="{{ $notificationUrl }}" target="_blank">{{ $resourceTitle }}</a></em>
    Yay!
</p>
@endsection
