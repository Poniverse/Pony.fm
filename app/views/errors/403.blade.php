@extends('shared._app_layout')

@section('app_content')
	<h1>403 - Not Authorized</h1>
	<p>You cannot do this.</p>
@endsection

@section('app_scripts')
	<script>
		window.pfm.error = 403;
	</script>
@endsection