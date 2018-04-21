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

module.exports = angular.module('ponyfm').controller "artists", [
    '$scope', 'artists', '$state'
    ($scope, artists, $state) ->

        refreshPages = (list) ->
            $scope.artists = list.artists
            $scope.currentPage = parseInt(list.current_page)
            $scope.totalPages = parseInt(list.total_pages)

            delete $scope.nextPage
            delete $scope.prevPage
            $scope.nextPage = $scope.currentPage + 1 if $scope.currentPage < $scope.totalPages
            $scope.prevPage = $scope.currentPage - 1 if $scope.currentPage > 1
            $scope.pages = [1..$scope.totalPages]

        artists.fetchList($state.params.page).done refreshPages
        $scope.$on 'artists-feteched', (e, list) -> refreshPages(list)

        $scope.gotoPage = (page) ->
            return if !page
            $state.transitionTo 'content.artists.list', {page: page}
]
