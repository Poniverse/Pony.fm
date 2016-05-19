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

window.pfm.preloaders['artist'] = [
    'artists', '$state'
    (artists, $state) ->
        artists.fetch $state.params.slug, true
]

module.exports = angular.module('ponyfm').controller "artist", [
    '$scope', 'artists', '$state', 'follow'
    ($scope, artists, $state, follow) ->
        artists.fetch($state.params.slug)
            .done (artistResponse) ->
                $scope.artist = artistResponse.artist
                $scope.gradient = {
                    'background-image': 'linear-gradient(135deg, ' + $scope.artist.avatar_colors[0] + ' 15%, ' + $scope.artist.avatar_colors[1] + ' 100%)'
                }

        $scope.toggleFollow = () ->
            follow.toggle('artist', $scope.artist.id).then (res) ->
                $scope.artist.user_data.is_following = res.is_followed
]
