@extends('emails.notifications._layout_plaintext')

@section('content')
{{ $creatorName }} left a comment on your Pony.fm profile!

Visit your profile with the following link to read it and reply:
{{ $notificationUrl }}
@endsection
