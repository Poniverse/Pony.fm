angular.module 'ponyfm', ['ui.bootstrap', 'ui.state', 'ui.date', 'ui.sortable'], [
	'$routeProvider', '$locationProvider', '$stateProvider', '$dialogProvider'
	(route, location, state, $dialogProvider) ->

		# Account

		state.state 'account',
			url: '/account'
			templateUrl: '/templates/account/settings.html'
			controller: 'account-settings'

		state.state 'account-content',
			url: '/account'
			abstract: true
			templateUrl: '/templates/account/content/_layout.html'

		state.state 'account-content.tracks',
			url: '/tracks'
			templateUrl: '/templates/account/content/tracks.html'
			controller: 'account-tracks'

		state.state 'account-content.tracks.edit',
			url: '/edit/:track_id'

		state.state 'account-content.albums',
			url: '/albums'
			templateUrl: '/templates/account/content/albums.html'
			controller: 'account-albums'

		state.state 'account-content.albums.create',
			url: '/create'
			templateUrl: '/templates/account/content/album.html'
			controller: 'account-albums-edit'

		state.state 'account-content.albums.edit',
			url: '/edit/:album_id'
			templateUrl: '/templates/account/content/album.html'
			controller: 'account-albums-edit'

		state.state 'account-content-playlists',
			url: '/account/playlists'
			templateUrl: '/templates/account/content/playlists.html'
			controller: 'account-playlists'

		state.state 'account-favourites',
			url: '/account/favourites'
			abstract: true
			templateUrl: '/templates/account/favourites/_layout.html'

		state.state 'account-favourites.tracks',
			url: ''
			templateUrl: '/templates/account/favourites/tracks.html'

		state.state 'account-favourites.playlists',
			url: '/playlists'
			templateUrl: '/templates/account/favourites/playlists.html'

		state.state 'account-favourites.albums',
			url: '/albums'
			templateUrl: '/templates/account/favourites/albums.html'

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

		state.state 'playlist',
			url: '/playlist/:id/:slug'
			templateUrl: '/templates/playlists/show.html'
			controller: 'playlist'

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