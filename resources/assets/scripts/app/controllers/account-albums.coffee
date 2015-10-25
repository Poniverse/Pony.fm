window.pfm.preloaders['account-albums'] = [
    'account-tracks', 'account-albums'
    (tracks, albums) ->
        $.when.all [tracks.refresh('published=true&in_album=false', true), albums.refresh(true)]
]

angular.module('ponyfm').controller "account-albums", [
    '$scope', '$state', 'account-albums', 'account-tracks'
    ($scope, $state, albums, tracks) ->

        $scope.albums = []
        $scope.data =
            isEditorOpen: false
            selectedAlbum: null
            tracksDb: []

        selectAlbum = (album) -> $scope.data.selectedAlbum = album

        updateTracks = (tracks) ->
            $scope.data.tracksDb.push track for track in tracks

        tracks.refresh('published=true&in_album=false').done updateTracks

        albumsDb = {}

        updateAlbums = (albums) ->
            $scope.albums.length = 0

            for album in albums
                $scope.albums.push album
                albumsDb[album.id] = album

            if $state.params.album_id
                selectAlbum albumsDb[$state.params.album_id]

        albums.refresh().done updateAlbums

        $scope.$on '$stateChangeSuccess', () ->
            if $state.params.album_id
                selectAlbum albumsDb[$state.params.album_id]
            else
                selectAlbum null

        $scope.$on 'album-created', () -> albums.refresh(true).done(updateAlbums)
        $scope.$on 'album-deleted', () -> albums.refresh(true).done(updateAlbums)
        $scope.$on 'album-updated', () -> tracks.refresh('published=true&in_album=false', true).done updateTracks
]
