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

module.exports = angular.module('ponyfm').directive 'pfmTracksList', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/tracks-list.html'
    replace: true
    scope:
        playlist: '='
        tracks: '=tracks'
        class: '@class'
        clickOverride: '&'
        hasOverride: '@'

    controller: [
        '$modal', '$scope', 'favourites', 'player', 'playlists', 'auth'
        ($modal, $scope, favourites, player, playlists, auth) ->
            $scope.auth = auth.data

            $scope.canModifyPlaylist = ->
                $scope.playlist and $scope.auth.isLogged and $scope.playlist.user.id == $scope.auth.user.id

            $scope.removeFromPlaylist = (track) ->
                $scope.track = track
                dialog = $modal
                    templateUrl: '/templates/partials/delete-playlist-track-dialog.html'
                    scope: $scope,
                    show: true

            $scope.confirmDeleteTrack = () ->
                playlists.removeTrackFromPlaylist $scope.playlist?.id, $scope.track.id
                .done ->
                    $scope.tracks = _.reject $scope.tracks, (t) -> t.id == $scope.track.id

            $scope.toggleFavourite = (track) ->
                favourites.toggle('track', track.id).done (res) ->
                    track.user_data.is_favourited = res.is_favourited

            $scope.play = (track) ->
                index = _.indexOf $scope.tracks, (t) -> t.id == track.id
                player.playTracks $scope.tracks, index
    ]
