# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Peter Deltchev
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

window.pfm.preloaders['album'] = [
    'albums', '$state', 'playlists'
    (albums, $state, playlists) ->
        $.when.all [albums.fetch $state.params.id, playlists.refreshOwned(true)]
]

module.exports = angular.module('ponyfm').controller "album", [
    '$scope', 'meta', 'albums', '$state', 'playlists', 'auth', '$mdDialog', 'download-cached', '$window', '$timeout'
    ($scope, meta, albums, $state, playlists, auth, $mdDialog, cachedAlbum, $window, $timeout) ->
        album = null

        albums.fetch($state.params.id).done (albumResponse) ->
            $scope.album = albumResponse.album
            album = albumResponse.album
            meta.setTitle("#{album.title} | #{album.user.name}")
            meta.setDescription("Listen to #{album.title}, the album by #{album.user.name}, and more on the largest pony music site.")

        $scope.playlists = []

        $scope.share = (ev) ->
            $mdDialog.show(
                templateUrl: '/templates/partials/album-share-dialog.html',
                scope: $scope,
                clickOutsideToClose: true,
                controller: ($scope, $mdDialog) ->
                    $scope.closeDialog = ->
                        $mdDialog.cancel()
            ).then (() ->
                return
            ), ->
                $scope.$apply()

        if auth.data.isLogged
            playlists.refreshOwned().done (lists) ->
                $scope.playlists.push list for list in lists

        $scope.checkMixedLosslessness = (format) ->
            if format.isMixedLosslessness == true
                $scope.format = format
                $modal({scope: $scope, templateUrl: 'templates/partials/collection-mixed-losslessness-dialog.html', show: true})


        $scope.getCachedAlbum = (id, format) ->
            $scope.isInProgress = true

            cachedAlbum.download('albums', id, format.name).then (response) ->
                $scope.albumUrl = response
                if $scope.albumUrl == 'error'
                    $scope.isInProgress = false
                else if $scope.albumUrl == 'pending'
                    # $timeout takes a callback function
                    # https://stackoverflow.com/a/23391203/3225811
                    $timeout(
                        $scope.getCachedAlbum(id, format)
                    , 5000)
                else
                    $scope.isInProgress = false
                    $window.open $scope.albumUrl
                    $scope.checkMixedLosslessness(format)
]
