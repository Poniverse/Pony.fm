window.pfm.preloaders['track'] = [
	'tracks', '$state'
	(tracks, $state) ->
		tracks.fetch $state.params.id
]

angular.module('ponyfm').controller "track", [
	'$scope', 'tracks', '$state'
	($scope, tracks, $state) ->
		tracks.fetch($state.params.id).done (trackResponse) ->
			$scope.track = trackResponse.track
]