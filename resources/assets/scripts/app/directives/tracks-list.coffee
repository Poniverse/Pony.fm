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

angular.module('ponyfm').directive 'pfmTracksList', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/tracks-list.html'
    replace: true
    scope:
        tracks: '=tracks',
        class: '@class'

    controller: [
        '$scope', 'favourites', 'player', 'auth'
        ($scope, favourites, player, auth) ->
            $scope.auth = auth.data

            $scope.toggleFavourite = (track) ->
                favourites.toggle('track', track.id).done (res) ->
                    track.user_data.is_favourited = res.is_favourited

            $scope.play = (track) ->
                index = _.indexOf $scope.tracks, (t) -> t.id == track.id
                player.playTracks $scope.tracks, index
    ]
