window.pfm.preloaders['track'] = [
    'tracks', '$state', 'playlists'
    (tracks, $state, playlists) ->
        $.when.all [tracks.fetch $state.params.id, playlists.refreshOwned(true)]
]

angular.module('ponyfm').controller "track", [
    '$scope', 'tracks', '$state', 'playlists', 'auth', 'favourites', '$dialog'
    ($scope, tracks, $state, playlists, auth, favourites, $dialog) ->
        track = null

        tracks.fetch($state.params.id).done (trackResponse) ->
            $scope.track = trackResponse.track
            track = trackResponse.track

        $scope.playlists = []

        if auth.data.isLogged
            playlists.refreshOwned().done (lists) ->
                $scope.playlists.push list for list in lists

        $scope.favouriteWorking = false

        $scope.toggleFavourite = (track) ->
            $scope.favouriteWorking = true
            favourites.toggle('track', track.id).done (res) ->
                track.is_favourited = res.is_favourited
                $scope.favouriteWorking = false

        $scope.share = () ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/track-share-dialog.html',
                controller: ['$scope', ($scope) -> $scope.track = track; $scope.close = () -> dialog.close()]
            dialog.open()

        $scope.addToNewPlaylist = () ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/playlist-dialog.html'
                controller: 'playlist-form'
                resolve: {
                    playlist: () ->
                        is_public: true
                        is_pinned: true
                        name: ''
                        description: ''
                }

            dialog.open().then (playlist) ->
                return if !playlist

                playlists.addTrackToPlaylist playlist.id, $scope.track.id
                $state.transitionTo 'playlist', {id: playlist.id}

        $scope.addToPlaylist = (playlist) ->
            return if playlist.message

            playlists.addTrackToPlaylist(playlist.id, $scope.track.id).done (res) ->
                playlist.message = res.message
]
