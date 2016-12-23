@extends('emails.notifications._layout')

@section('content')
<p>
    {{ $creatorName }} left a comment on your Pony.fm profile!
    <a href="{{ $notificationUrl }}" target="_blank">Visit your profile</a> to
    read it and reply.
</p>
@endsection
