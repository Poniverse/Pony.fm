window.pfm.preloaders['favourites-playlists'] = [
	'favourites'
	(favourites) ->
		favourites.fetchPlaylists(true)
]

angular.module('ponyfm').controller "favourites-playlists", [
	'$scope', 'favourites'
	($scope, favourites) ->
		favourites.fetchPlaylists().done (res) ->
			$scope.playlists = res.playlists
]