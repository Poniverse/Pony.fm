window.pfm.preloaders['dashboard'] = [
	'dashboard'
	(dashboard) -> dashboard.refresh(true)
]

angular.module('ponyfm').controller "dashboard", [
	'$scope', 'dashboard', 'auth', '$http'
	($scope, dashboard, auth, $http) ->
		$scope.recentTracks = null
		$scope.popularTracks = null
		$scope.news = null

		dashboard.refresh().done (res) ->
			$scope.recentTracks = res.recent_tracks
			$scope.popularTracks = res.popular_tracks
			$scope.news = res.news

		$scope.markAsRead = (post) ->
			if auth.data.isLogged
				$http.post('/api/web/dashboard/read-news', {url: post.url, _token: window.pfm.token}).success ->
					post.read = true
]