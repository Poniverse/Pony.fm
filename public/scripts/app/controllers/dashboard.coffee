angular.module('ponyfm').controller "dashboard", [
	'$scope'
	($scope) ->
		$scope.recentTracks = null
		$scope.popularTracks = null

		$scope.refresh = () ->
			$.getJSON('/api/web/dashboard')
				.done (res) -> $scope.$apply ->
					$scope.recentTracks = res.recent_tracks
					$scope.popularTracks = res.popular_tracks

		$scope.refresh()
]