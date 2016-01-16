# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Peter Deltchev
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

angular.module('ponyfm').directive 'pfmSearch', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/search.html'
    scope:
        resource: '=resource',
        type: '@type'

    controller: [
        '$scope', 'search'
        ($scope, search) ->
            $scope.searchQuery = null
            $scope.searchInProgress = false

            $scope.tracks = []
            $scope.albums = []
            $scope.playlists = []
            $scope.users = []

            clearResults = ()->
                $scope.tracks = []
                $scope.albums = []
                $scope.playlists = []
                $scope.users = []

            $scope.quickSearch = ()->
                clearResults()
                $scope.searchInProgress = true

                search.searchAllContent($scope.searchQuery)
                    .done (results)->
                        for track in results.tracks
                            $scope.tracks.push(track)

                        for album in results.albums
                            $scope.albums.push(album)

                        for playlist in results.playlists
                            $scope.playlists.push(playlist)

                        for user in results.users
                            $scope.users.push(user)

    ]
