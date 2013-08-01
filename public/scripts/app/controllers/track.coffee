window.pfm.preloaders['track'] = [
	'tracks', '$state', 'playlists'
	(tracks, $state, playlists) ->
		$.when.all [tracks.fetch $state.params.id, playlists.refreshOwned(true)]
]

angular.module('ponyfm').controller "track", [
	'$scope', 'tracks', '$state', 'playlists', 'auth'
	($scope, tracks, $state, playlists, auth) ->
		tracks.fetch($state.params.id).done (trackResponse) ->
			$scope.track = trackResponse.track

		$scope.playlists = []

		if auth.data.isLogged
			playlists.refreshOwned().done (lists) ->
				$scope.playlists.push list for list in lists
]