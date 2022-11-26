# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Feld0
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

module.exports = angular.module('ponyfm').controller "account-albums", [
    '$scope', '$state', 'account-albums', 'account-tracks'
    ($scope, $state, albums, tracks) ->
        $scope.albums = []
        $scope.data =
            isEditorOpen: false
            selectedAlbum: null
            tracksDb: []

        selectAlbum = (album) -> $scope.data.selectedAlbum = album

        updateTracks = (tracks) ->
            $scope.data.tracksDb.length = 0
            $scope.data.tracksDb.push track for track in tracks

        tracks.refresh('published=true&in_album=false', false, $state.params.slug).done updateTracks

        albumsDb = {}

        updateAlbums = (albums) ->
            albumsDb.length = 0
            $scope.albums.length = 0

            for album in albums
                $scope.albums.push album
                albumsDb[album.id] = album

            if $state.params.album_id
                selectAlbum albumsDb[$state.params.album_id]

        albums.refresh(false, $state.params.slug).done updateAlbums

        $scope.$on '$stateChangeSuccess', () ->
            if $state.params.album_id
                selectAlbum albumsDb[$state.params.album_id]
            else
                selectAlbum null

        $scope.$on 'album-created', () -> albums.refresh(true, $state.params.slug).done(updateAlbums)
        $scope.$on 'album-deleted', () -> albums.refresh(true, $state.params.slug).done(updateAlbums)
        $scope.$on 'album-updated', () -> tracks.refresh('published=true&in_album=false', true, $state.params.slug).done(updateTracks)
]
