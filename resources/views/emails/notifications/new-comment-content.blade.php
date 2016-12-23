@extends('emails.notifications._layout')

@section('content')
<p>
    {{ $creatorName }} left a comment on your {{ $resourceType }},
    <a href="{{ $notificationUrl }}" target="_blank"><em>{{ $resourceTitle }}</em></a>!
    <a href="{{ $notificationUrl }}" target="_blank">Visit it</a> to read the comment and reply.
</p>
@endsection
