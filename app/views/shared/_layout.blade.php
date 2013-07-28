<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<title>Pony.fm SPA</title>
		<meta name="description" content="" />
		<meta name="viewport" content="width=device-width" />
		<base href="/" />

		@yield('styles')
	</head>
	<body ng-app="ponyfm" ng-controller="application" uploader>
		<div class="background-color"></div>
		<div class="background-two"></div>
		<div class="background-one"></div>
		@yield('content')
		@yield('scripts')
	</body>
</html>