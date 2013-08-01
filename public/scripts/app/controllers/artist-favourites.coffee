window.pfm.preloaders['artist-favourites'] = [
	'artists', '$state'
	(artists, $state) ->
		artists.fetchFavourites $state.params.slug, true
]

angular.module('ponyfm').controller "artist-favourites", [
	'$scope', 'artists', '$state'
	($scope, artists, $state) ->
		artists.fetchFavourites($state.params.slug).done (artistResponse) ->
			$scope.favourites = artistResponse
			console.log artistResponse
]