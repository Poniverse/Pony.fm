window.pfm.preloaders['account-tracks'] = [
    'account-tracks', 'account-albums', 'taxonomies'
    (tracks, albums, taxonomies) ->
        $.when.all [tracks.refresh(null, true), albums.refresh(true), taxonomies.refresh()]
]

angular.module('ponyfm').controller "account-tracks", [
    '$scope', '$state', 'taxonomies', '$dialog', 'lightbox', 'account-albums', 'account-tracks'
    ($scope, $state, taxonomies, $dialog, lightbox, albums, tracks) ->
        $scope.data =
            selectedTrack: null

        $scope.tracks = []

        tracksDb = {}

        setTracks = (tracks) ->
            $scope.tracks.length = 0
            tracksDb = {}
            for track in tracks
                tracksDb[track.id] = track
                $scope.tracks.push track

            if $state.params.track_id
                $scope.data.selectedTrack = tracksDb[$state.params.track_id]

        tracks.refresh().done setTracks

        $scope.refreshList = () ->
            tracks.refresh().done setTracks

        $scope.selectTrack = (track) ->
            $scope.data.selectedTrack = track

        $scope.$on '$stateChangeSuccess', () ->
            if $state.params.track_id
                $scope.selectTrack tracksDb[$state.params.track_id]
            else
                $scope.selectTrack null

        $scope.$on 'track-deleted', () ->
            tracks.clearCache()
            $scope.refreshList()
]
