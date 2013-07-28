angular.module('ponyfm').controller 'playlist', [
	'$scope', '$state'
	($scope, $state) ->
		$scope.refresh = () ->
			$.getJSON('/api/web/playlists/show/' + $state.params.id)
				.done (playlist) -> $scope.$apply ->
					$scope.playlist = playlist

		$scope.refresh()
]