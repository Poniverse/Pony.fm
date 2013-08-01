window.pfm.preloaders['playlist'] = [
	'$state', 'playlists'
	($state, playlists) ->
		playlists.fetch $state.params.id, true
]

angular.module('ponyfm').controller 'playlist', [
	'$scope', '$state', 'playlists'
	($scope, $state, playlists) ->
		playlists.fetch($state.params.id).done (playlist) ->
			$scope.playlist = playlist
]