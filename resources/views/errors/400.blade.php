@extends('shared._app_layout')

@section('app_content')
	<h1>400 - Invalid Request</h1>
	<p>Your request was invalid.</p>
@endsection

@section('app_scripts')
	<script>
		window.pfm.error = 400;
	</script>
@endsection