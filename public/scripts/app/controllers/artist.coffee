window.pfm.preloaders['artist'] = [
	'artists', '$state'
	(artists, $state) ->
		artists.fetch $state.params.slug
]

angular.module('ponyfm').controller "artist", [
	'$scope', 'artists', '$state'
	($scope, artists, $state) ->
		artists.fetch($state.params.slug).done (artistResponse) ->
			$scope.artist = artistResponse.artist
]