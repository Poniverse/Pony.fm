window.pfm.preloaders['favourites-tracks'] = [
	'favourites'
	(favourites) ->
		favourites.fetchTracks(true)
]

angular.module('ponyfm').controller "favourites-tracks", [
	'$scope', 'favourites'
	($scope, favourites) ->
		favourites.fetchTracks().done (res) ->
			$scope.tracks = res.tracks
]