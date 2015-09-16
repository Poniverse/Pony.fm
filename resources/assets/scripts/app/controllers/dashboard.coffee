window.pfm.preloaders['dashboard'] = [
	'dashboard'
	(dashboard) -> dashboard.refresh(true)
]

angular.module('ponyfm').controller "dashboard", [
	'$scope', 'dashboard', 'auth', '$http'
	($scope, dashboard, auth, $http) ->
		$scope.recentTracks = null
		$scope.popularTracks = null

		dashboard.refresh().done (res) ->
			$scope.recentTracks = res.recent_tracks
			$scope.popularTracks = res.popular_tracks
]
