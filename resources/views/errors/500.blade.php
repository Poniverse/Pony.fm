@extends('shared._app_layout')

@section('app_content')
	<h1>400 - Server Error</h1>
	<p>There was an error in the server.</p>
@endsection

@section('app_scripts')
	<script>
		window.pfm.error = 500;
	</script>
@endsection