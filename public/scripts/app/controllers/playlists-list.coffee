window.pfm.preloaders['playlists-list'] = [
	'playlists', '$state'
	(playlists, $state) ->
		playlists.fetchList($state.params.page, true)
]

angular.module('ponyfm').controller "playlists-list", [
	'$scope', 'playlists', '$state',
	($scope, playlists, $state) ->
		playlists.fetchList($state.params.page).done (searchResults) ->
			$scope.playlists = searchResults.playlists
]