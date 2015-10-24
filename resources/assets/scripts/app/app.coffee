window.pfm.preloaders = {}

module = angular.module 'ponyfm', ['ui.bootstrap', 'ui.state', 'ui.date', 'ui.sortable', 'pasvaz.bindonce', 'angularytics']

if window.pfm.environment == 'production'
	module.run [
		'Angularytics',
		(analytics) ->
			analytics.init()
	]

module.config [
	'$locationProvider', '$stateProvider', '$dialogProvider', 'AngularyticsProvider', '$httpProvider', '$sceDelegateProvider'
	(location, state, $dialogProvider, analytics, $httpProvider, $sceDelegateProvider) ->

		$httpProvider.interceptors.push [
			->
				request: (config) ->
					return config if !(/^\/?templates\//.test config.url)
					config.url += '?' + Math.ceil(Math.random() * 1000000)
					return config
		]

		# This fixes resource loading on IE
		$sceDelegateProvider.resourceUrlWhitelist [
			'self',
			'/templates/directives/*'
		]

		if window.pfm.environment == 'production'
			analytics.setEventHandlers ['Google']

		# Errors
		state.state 'errors-404',
			url: '/errors/not-found'
			templateUrl: '/templates/errors/404.html'

		state.state 'errors-500',
			url: '/errors/server'
			templateUrl: '/templates/errors/500.html'

		state.state 'errors-403',
			url: '/errors/not-authorized'
			templateUrl: '/templates/errors/403.html'

		state.state 'errors-400',
			url: '/errors/invalid'
			templateUrl: '/templates/errors/400.html'

		# Upload

		state.state 'uploader',
			url: '/account/uploader'
			templateUrl: '/templates/uploader/index.html'
			controller: 'uploader'

		# Account

		state.state 'account',
			url: '/account'
			abstract: true
			templateUrl: '/templates/account/_layout.html'

		state.state 'account.settings',
			url: ''
			templateUrl: '/templates/account/settings.html'
			controller: 'account-settings'

		state.state 'account.tracks',
			url: '/tracks'
			templateUrl: '/templates/account/tracks.html'
			controller: 'account-tracks'

		state.state 'account.tracks.edit',
			url: '/edit/:track_id'
			templateUrl: '/templates/account/track.html'
			controller: 'account-track'

		state.state 'account.albums',
			url: '/albums'
			templateUrl: '/templates/account/albums.html'
			controller: 'account-albums'

		state.state 'account.albums.create',
			url: '/create'
			templateUrl: '/templates/account/album.html'
			controller: 'account-albums-edit'

		state.state 'account.albums.edit',
			url: '/edit/:album_id'
			templateUrl: '/templates/account/album.html'
			controller: 'account-albums-edit'

		state.state 'account.playlists',
			url: '/playlists'
			templateUrl: '/templates/account/playlists.html'
			controller: 'account-playlists'

		state.state 'favourites',
			url: '/account/favourites'
			abstract: true
			templateUrl: '/templates/favourites/_layout.html'

		state.state 'favourites.tracks',
			url: '/tracks'
			templateUrl: '/templates/favourites/tracks.html'
			controller: 'favourites-tracks'

		state.state 'favourites.playlists',
			url: '/playlists'
			templateUrl: '/templates/favourites/playlists.html'
			controller: 'favourites-playlists'

		state.state 'favourites.albums',
			url: '/albums'
			templateUrl: '/templates/favourites/albums.html'
			controller: 'favourites-albums'

		# Tracks

		state.state 'content',
			abstract: true
			templateUrl: '/templates/content/_layout.html'

		state.state 'content.tracks',
			templateUrl: '/templates/tracks/index.html'
			controller: 'tracks'
			url: '/tracks'
			abstract: true

		state.state 'content.tracks.list',
			url: '^/tracks?filter&page'
			templateUrl: '/templates/tracks/list.html'
			controller: 'tracks-list'

		state.state 'content.track',
			url: '/tracks/{id:[^\-]+}-{slug}'
			templateUrl: '/templates/tracks/show.html'
			controller: 'track'

		# Albums

		state.state 'content.albums',
			url: '/albums'
			templateUrl: '/templates/albums/index.html'
			controller: 'albums'
			abstract: true

		state.state 'content.albums.list',
			url: '?page'
			templateUrl: '/templates/albums/list.html'
			controller: 'albums-list'

		state.state 'content.album',
			url: '/albums/{id:[^\-]+}-{slug}'
			templateUrl: '/templates/albums/show.html'
			controller: 'album'

		# Playlists

		state.state 'content.playlists',
			url: '/playlists'
			templateUrl: '/templates/playlists/index.html'
			controller: 'playlists'
			abstract: true

		state.state 'content.playlists.list',
			url: '?page'
			controller: 'playlists-list'
			templateUrl: '/templates/playlists/list.html'

		state.state 'content.playlist',
			url: '/playlist/{id:[^\-]+}-{slug}'
			templateUrl: '/templates/playlists/show.html'
			controller: 'playlist'

		# Artists

		state.state 'content.artists',
			url: '/artists'
			templateUrl: '/templates/artists/index.html'
			controller: 'artists'
			abstract: true

		state.state 'content.artists.list',
			url: '?page'
			templateUrl: '/templates/artists/list.html'
			controller: 'artists-list'

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

		if window.pfm.auth.isLogged
			state.state 'home',
				url: '/'
				templateUrl: '/templates/dashboard/index.html'
				controller: 'dashboard'
		else
			state.state 'home',
				url: '/'
				templateUrl: '/templates/home/index.html'
				controller: 'home'

		# Final catch-all for aritsts
		state.state 'content.artist',
			url: '^/{slug}'
			templateUrl: '/templates/artists/_show_layout.html'
			abstract: true
			controller: 'artist'

		state.state 'content.artist.profile',
			url: ''
			templateUrl: '/templates/artists/profile.html'
			controller: 'artist-profile'

		state.state 'content.artist.content',
			url: '/content'
			templateUrl: '/templates/artists/content.html'
			controller: 'artist-content'

		state.state 'content.artist.favourites',
			url: '/favourites'
			templateUrl: '/templates/artists/favourites.html'
			controller: 'artist-favourites'

		location.html5Mode(true);
		$dialogProvider.options
			dialogFade: true
			backdropClick: false
]
