window.pfm.preloaders['artist-favourites'] = [
	'artists', '$state'
	(artists, $state) ->
		artists.fetch $state.params.slug, true
]

angular.module('ponyfm').controller "artist-favourites", [
	'$scope', 'artists', '$state'
	($scope, artists, $state) ->
		artists.fetch($state.params.slug).done (artistResponse) ->
			$scope.artist = artistResponse.artist
]