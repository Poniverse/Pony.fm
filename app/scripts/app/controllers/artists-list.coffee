window.pfm.preloaders['artists-list'] = [
	'artists', '$state'
	(artists, $state) ->
		artists.fetchList($state.params.page, true)
]

angular.module('ponyfm').controller "artists-list", [
	'$scope', 'artists', '$state'
	($scope, artists, $state) ->
		artists.fetchList($state.params.page).done (list) ->
			$scope.artists = list.artists
]