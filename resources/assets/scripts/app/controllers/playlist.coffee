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

angular.module('ponyfm').controller 'playlist', [
    '$scope', '$state', 'playlists', '$dialog', 'download-cached', '$window', '$timeout'
    ($scope, $state, playlists, $dialog, cachedPlaylist, $window, $timeout) ->
        playlist = null

        playlists.fetch($state.params.id).done (playlistResponse) ->
            $scope.playlist = playlistResponse
            playlist = playlistResponse

        $scope.share = () ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/playlist-share-dialog.html',
                controller: ['$scope', ($scope) -> $scope.playlist = playlist; $scope.close = () -> dialog.close()]
            dialog.open()

        $scope.getCachedPlaylist = (id, format) ->
            $scope.isInProgress = true

            cachedPlaylist.download('playlists', id, format).then (response) ->
                $scope.playlistUrl = response
                if $scope.playlistUrl == 'error'
                    $scope.isInProgress = false
                else if $scope.playlistUrl == 'pending'
                    $timeout $scope.getCachedPlaylist(id, format), 5000
                else
                    $scope.isInProgress = false
                    $window.open $scope.playlistUrl
]

