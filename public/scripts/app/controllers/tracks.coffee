angular.module('ponyfm').controller "tracks", [
	'$scope'
	($scope) ->
		$scope.recentTracks = null

		$scope.refresh = () ->
			$.getJSON('/api/web/tracks/recent')
				.done (res) -> $scope.$apply ->
					$scope.recentTracks = res

		$scope.refresh()
]