@extends('shared._app_layout')

@section('app_content')
	<h1>404 - Not Found</h1>
	<p>We could not find what you were looking for.</p>
@endsection

@section('app_scripts')
	<script>
		window.pfm.error = 404;
	</script>
@endsection