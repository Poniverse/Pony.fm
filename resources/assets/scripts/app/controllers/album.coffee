window.pfm.preloaders['album'] = [
    'albums', '$state', 'playlists'
    (albums, $state, playlists) ->
        $.when.all [albums.fetch $state.params.id, playlists.refreshOwned(true)]
]

angular.module('ponyfm').controller "album", [
    '$scope', 'albums', '$state', 'playlists', 'auth', '$dialog'
    ($scope, albums, $state, playlists, auth, $dialog) ->
        album = null

        albums.fetch($state.params.id).done (albumResponse) ->
            $scope.album = albumResponse.album
            album = albumResponse.album

        $scope.playlists = []

        $scope.share = () ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/album-share-dialog.html',
                controller: ['$scope', ($scope) -> $scope.album = album; $scope.close = () -> dialog.close()]
            dialog.open()

        if auth.data.isLogged
            playlists.refreshOwned().done (lists) ->
                $scope.playlists.push list for list in lists
]
