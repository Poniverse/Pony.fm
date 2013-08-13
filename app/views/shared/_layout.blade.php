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
		@yield('content')
		@yield('scripts')
	</body>
</html>