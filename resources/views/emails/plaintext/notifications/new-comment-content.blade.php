@extends('emails.plaintext.notifications._layout')

@section('content')
{{ $creatorName }} left a comment on your {{ $resourceType }}, "{{ $resourceTitle }}"!

Visit the following link to read the comment and reply:
{{ $notificationUrl }}
@endsection
