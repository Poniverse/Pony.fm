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

window.pfm.preloaders['playlist'] = [
    '$state', 'playlists'
    ($state, playlists) ->
        playlists.fetch $state.params.id, true
]

module.exports = angular.module('ponyfm').controller 'playlist', [
    '$scope', 'meta', '$state', 'playlists', '$mdDialog', 'download-cached', '$window', '$timeout'
    ($scope, meta, $state, playlists, $mdDialog, cachedPlaylist, $window, $timeout) ->
        playlist = null

        playlists.fetch($state.params.id).done (playlistResponse) ->
            $scope.playlist = playlistResponse
            playlist = playlistResponse
            meta.setTitle("#{playlist.title} | #{playlist.user.name}")
            meta.setDescription("Listen to \"#{playlist.title}\", a playlist by #{playlist.user.name}, on the largest pony music site.")

        $scope.share = (ev) ->
            $mdDialog.show(
                templateUrl: '/templates/partials/playlist-share-dialog.html',
                scope: $scope,
                clickOutsideToClose: true,
                controller: ($scope, $mdDialog) ->
                    $scope.closeDialog = ->
                        $mdDialog.cancel()
            ).then (() ->
                return
            ), ->
                $scope.$apply()

        $scope.checkMixedLosslessness = (format) ->
            if format.isMixedLosslessness == true
                $scope.format = format
                $modal({scope: $scope, templateUrl: 'templates/partials/collection-mixed-losslessness-dialog.html', show: true})


        $scope.getCachedPlaylist = (id, format) ->
            $scope.isInProgress = true

            cachedPlaylist.download('playlists', id, format.name).then (response) ->
                $scope.playlistUrl = response
                if $scope.playlistUrl == 'error'
                    $scope.isInProgress = false
                else if $scope.playlistUrl == 'pending'
                    # $timeout takes a callback function
                    # https://stackoverflow.com/a/23391203/3225811
                    $timeout(
                        () ->$scope.getCachedPlaylist(id, format)
                    , 5000)
                else
                    $scope.isInProgress = false
                    $window.open $scope.playlistUrl
                    $scope.checkMixedLosslessness(format)
]
