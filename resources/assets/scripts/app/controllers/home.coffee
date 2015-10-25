window.pfm.preloaders['home'] = [
    'dashboard'
    (dashboard) -> dashboard.refresh(true)
]

angular.module('ponyfm').controller "home", [
    '$scope', 'dashboard'
    ($scope, dashboard) ->
        $scope.recentTracks = null
        $scope.popularTracks = null

        dashboard.refresh().done (res) ->
            $scope.recentTracks = res.recent_tracks
            $scope.popularTracks = res.popular_tracks
]
