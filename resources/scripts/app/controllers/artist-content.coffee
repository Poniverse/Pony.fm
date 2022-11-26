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

window.pfm.preloaders['artist-content'] = [
    'artists', '$state'
    (artists, $state) ->
        $.when.all [artists.fetch($state.params.slug), artists.fetchContent($state.params.slug, true)]
]

module.exports = angular.module('ponyfm').controller "artist-content", [
    '$scope', 'artists', '$state'
    ($scope, artists, $state) ->
        artists.fetchContent($state.params.slug)
            .done (artistResponse) ->
                $scope.content = artistResponse
]
