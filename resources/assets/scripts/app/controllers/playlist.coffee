window.pfm.preloaders['playlist'] = [
    '$state', 'playlists'
    ($state, playlists) ->
        playlists.fetch $state.params.id, true
]

angular.module('ponyfm').controller 'playlist', [
    '$scope', '$state', 'playlists', '$dialog'
    ($scope, $state, playlists, $dialog) ->
        playlist = null

        playlists.fetch($state.params.id).done (playlistResponse) ->
            $scope.playlist = playlistResponse
            playlist = playlistResponse

        $scope.share = () ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/playlist-share-dialog.html',
                controller: ['$scope', ($scope) -> $scope.playlist = playlist; $scope.close = () -> dialog.close()]
            dialog.open()
]
