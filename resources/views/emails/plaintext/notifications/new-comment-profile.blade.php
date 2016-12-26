@extends('emails.plaintext.notifications._layout')

@section('content')
{{ $creatorName }} left a comment on your Pony.fm profile!

Visit your profile with the following link to read it and reply:
{{ $notificationUrl }}
@endsection
