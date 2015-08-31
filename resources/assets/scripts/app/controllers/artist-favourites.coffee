window.pfm.preloaders['artist-favourites'] = [
	'artists', '$state'
	(artists, $state) ->
		$.when.all [artists.fetch($state.params.slug), artists.fetchFavourites($state.params.slug, true)]
]

angular.module('ponyfm').controller "artist-favourites", [
	'$scope', 'artists', '$state'
	($scope, artists, $state) ->
		artists.fetchFavourites($state.params.slug).done (artistResponse) ->
			$scope.favourites = artistResponse
]