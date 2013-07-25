angular.module 'ponyfm', ['ui.bootstrap', 'ui.state'], [
	'$routeProvider', '$locationProvider', '$stateProvider', '$dialogProvider'
	(route, location, state, $dialogProvider) ->

		# Account

		state.state 'account',
			url: '/account'
			templateUrl: '/templates/account/settings.html'
			controller: 'account-settings'

		state.state 'account-content',
			url: '/account/content'
			abstract: true
			templateUrl: '/templates/account/content/_layout.html'

		state.state 'account-content.tracks',
			url: '/tracks'
			templateUrl: '/templates/account/content/tracks.html'
			controller: 'account-content-tracks'

		state.state 'account-content.tracks.edit',
			url: '/:track_id'

		state.state 'account-content.albums',
			url: '/albums'
			templateUrl: '/templates/account/content/albums.html'

		state.state 'account-content.playlists',
			url: '/playlists'
			templateUrl: '/templates/account/content/playlists.html'

		state.state 'account-favorites',
			url: '/account/favorites'
			abstract: true
			templateUrl: '/templates/account/favorites/_layout.html'

		state.state 'account-favorites.tracks',
			url: ''
			templateUrl: '/templates/account/favorites/tracks.html'

		state.state 'account-favorites.playlists',
			url: '/playlists'
			templateUrl: '/templates/account/favorites/playlists.html'

		state.state 'account-favorites.albums',
			url: '/albums'
			templateUrl: '/templates/account/favorites/albums.html'

		# Tracks

		state.state 'tracks',
			url: '/tracks'
			templateUrl: '/templates/tracks/_layout.html'
			abstract: true

		state.state 'tracks.list',
			url: ''
			templateUrl: '/templates/tracks/index.html'

		state.state 'tracks.random',
			url: '/random'
			templateUrl: '/templates/tracks/index.html'

		state.state 'tracks.popular',
			url: '/popular'
			templateUrl: '/templates/tracks/index.html'

		# Albums

		state.state 'albums',
			url: '/albums'
			templateUrl: '/templates/albums/index.html'

		# Playlists

		state.state 'playlists',
			url: '/playlists'
			templateUrl: '/templates/playlists/index.html'

		# Artists

		state.state 'artists',
			url: '/artists'
			templateUrl: '/templates/artists/index.html'

		# Pages

		state.state 'faq',
			url: '/faq'
			templateUrl: '/templates/pages/faq.html'

		state.state 'about',
			url: '/about'
			templateUrl: '/templates/pages/about.html'

		# Auth

		state.state 'login',
			url: '/login'
			templateUrl: '/templates/auth/login.html'
			controller: 'login'

		state.state 'register',
			url: '/register'
			templateUrl: '/templates/auth/register.html'

		# Hompage

		state.state 'home',
			url: '/'
			templateUrl: '/templates/home/index.html'

		route.otherwise '/'

		location.html5Mode(true);
		$dialogProvider.options
			dialogFade: true
			backdropClick: false
]