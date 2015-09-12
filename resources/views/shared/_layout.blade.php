<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<title>Pony.fm</title>
		<meta name="description" content="" />
		<meta name="viewport" content="width=device-width" />
		<base href="/" />

		@yield('styles')
	</head>
	<body ng-app="ponyfm" ng-controller="application" class="{{Auth::check() ? 'is-logged' : ''}}">
		@yield('content')
		@yield('scripts')
	</body>
</html>