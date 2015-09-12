window.pfm.preloaders['artist-profile'] = [
	'artists', '$state'
	(artists, $state) ->
		artists.fetch $state.params.slug, true
]

angular.module('ponyfm').controller "artist-profile", [
	'$scope', 'artists', '$state'
	($scope, artists, $state) ->
]