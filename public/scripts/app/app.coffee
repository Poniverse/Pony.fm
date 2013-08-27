window.pfm.preloaders = {}

module = angular.module 'ponyfm', ['ui.bootstrap', 'ui.state', 'ui.date', 'ui.sortable', 'pasvaz.bindonce']

module.config [
	'$locationProvider', '$stateProvider', '$dialogProvider'
	(location, state, $dialogProvider) ->

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
			templateUrl: '/templates/account/content/track.html'
			controller: 'account-tracks-edit'

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

		state.state 'content',
			abstract: true
			templateUrl: '/templates/content/_layout.html'

		state.state 'content.tracks',
			templateUrl: '/templates/tracks/search.html'
			controller: 'tracks'
			url: '/tracks'
			abstract: true

		state.state 'content.tracks.list',
			url: '^/tracks?filter&page'
			templateUrl: '/templates/tracks/search-list.html'
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