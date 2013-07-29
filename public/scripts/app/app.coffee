angular.module 'ponyfm', ['ui.bootstrap', 'ui.state', 'ui.date', 'ui.sortable'], [
	'$routeProvider', '$locationProvider', '$stateProvider', '$dialogProvider'
	(route, location, state, $dialogProvider) ->

		# Account

		state.state 'account',
			url: '/account'
			templateUrl: '/templates/account/settings.html'
			controller: 'account-settings'
			navigation:
				index: 9

		state.state 'account-content',
			url: '/account'
			abstract: true
			templateUrl: '/templates/account/content/_layout.html'
			navigation:
				index: 8

		state.state 'account-content.tracks',
			url: '/tracks'
			templateUrl: '/templates/account/content/tracks.html'
			controller: 'account-tracks'
			navigation:
				index: 8
				subIndex: 1

		state.state 'account-content.tracks.edit',
			url: '/edit/:track_id'
			navigation:
				index: 8
				subIndex: 1

		state.state 'account-content.albums',
			url: '/albums'
			templateUrl: '/templates/account/content/albums.html'
			controller: 'account-albums'
			navigation:
				index: 8
				subIndex: 2

		state.state 'account-content.albums.create',
			url: '/create'
			templateUrl: '/templates/account/content/album.html'
			controller: 'account-albums-edit'
			navigation:
				index: 8
				subIndex: 2

		state.state 'account-content.albums.edit',
			url: '/edit/:album_id'
			templateUrl: '/templates/account/content/album.html'
			controller: 'account-albums-edit'
			navigation:
				index: 8
				subIndex: 2

		state.state 'account-content-playlists',
			url: '/account/playlists'
			templateUrl: '/templates/account/content/playlists.html'
			controller: 'account-playlists'
			navigation:
				index: 6

		state.state 'account-favourites',
			url: '/account/favourites'
			abstract: true
			templateUrl: '/templates/account/favourites/_layout.html'
			navigation:
				index: 7

		state.state 'account-favourites.tracks',
			url: ''
			templateUrl: '/templates/account/favourites/tracks.html'
			navigation:
				index: 7
				subIndex: 1

		state.state 'account-favourites.playlists',
			url: '/playlists'
			templateUrl: '/templates/account/favourites/playlists.html'
			navigation:
				index: 7
				subIndex: 3

		state.state 'account-favourites.albums',
			url: '/albums'
			templateUrl: '/templates/account/favourites/albums.html'
			navigation:
				index: 7
				subIndex: 2

		# Tracks

		state.state 'tracks',
			url: '/tracks'
			templateUrl: '/templates/tracks/index.html'
			controller: 'tracks'
			navigation:
				index: 2

		# Albums

		state.state 'albums',
			url: '/albums'
			templateUrl: '/templates/albums/index.html'
			navigation:
				index: 3

		# Playlists

		state.state 'playlists',
			url: '/playlists'
			templateUrl: '/templates/playlists/index.html'
			navigation:
				index: 4

		state.state 'playlist',
			url: '/playlist/:id/:slug'
			templateUrl: '/templates/playlists/show.html'
			controller: 'playlist'
			navigation:
				index: 4

		# Artists

		state.state 'artists',
			url: '/artists'
			templateUrl: '/templates/artists/index.html'
			navigation:
				index: 5

		# Pages

		state.state 'faq',
			url: '/faq'
			templateUrl: '/templates/pages/faq.html'
			navigation:
				index: 11

		state.state 'about',
			url: '/about'
			templateUrl: '/templates/pages/about.html'
			navigation:
				index: 10

		# Auth

		state.state 'login',
			url: '/login'
			templateUrl: '/templates/auth/login.html'
			controller: 'login'
			navigation:
				index: 12

		state.state 'register',
			url: '/register'
			templateUrl: '/templates/auth/register.html'
			navigation:
				index: 13

		# Hompage

		if window.pfm.auth.isLogged
			state.state 'home',
				url: '/'
				templateUrl: '/templates/dashboard.html'
				controller: 'dashboard'
				navigation:
					index: 0
		else
			state.state 'home',
				url: '/'
				templateUrl: '/templates/home/index.html'
				navigation:
					index: 0

		route.otherwise '/'

		location.html5Mode(true);
		$dialogProvider.options
			dialogFade: true
			backdropClick: false
]