window.pfm.preloaders['album'] = [
	'albums', '$state', 'playlists'
	(albums, $state, playlists) ->
		$.when.all [albums.fetch $state.params.id, playlists.refreshOwned(true)]
]

angular.module('ponyfm').controller "album", [
	'$scope', 'albums', '$state', 'playlists', 'auth'
	($scope, albums, $state, playlists, auth) ->
		albums.fetch($state.params.id).done (albumResponse) ->
			$scope.album = albumResponse.album

		$scope.playlists = []

		if auth.data.isLogged
			playlists.refreshOwned().done (lists) ->
				$scope.playlists.push list for list in lists
]