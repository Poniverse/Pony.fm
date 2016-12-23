@extends('emails.notifications._layout')

@section('content')
<p>
    Congrats!
    <a href="{{ $notificationUrl }}" target="_blank">{{ $creatorName }}</a>
    is now following you on Pony.fm!
</p>
@endsection
