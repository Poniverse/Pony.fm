window.pfm.preloaders['dashboard'] = [
	'dashboard'
	(dashboard) -> dashboard.refresh(true)
]

angular.module('ponyfm').controller "dashboard", [
	'$scope', 'dashboard'
	($scope, dashboard) ->
		$scope.recentTracks = null
		$scope.popularTracks = null
		$scope.news = null

		dashboard.refresh().done (res) ->
			$scope.recentTracks = res.recent_tracks
			$scope.popularTracks = res.popular_tracks
			$scope.news = res.news
]