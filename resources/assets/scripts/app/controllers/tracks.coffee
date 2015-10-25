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

window.pfm.preloaders['tracks'] = [
    'tracks', '$state'
    (tracks) ->
        tracks.loadFilters()
]

angular.module('ponyfm').controller "tracks", [
    '$scope', 'tracks', '$state'
    ($scope, tracks, $state) ->
        $scope.recentTracks = null
        $scope.query = tracks.mainQuery
        $scope.filters = tracks.filters

        $scope.toggleListFilter = (filter, id) ->
            $scope.query.toggleListFilter filter, id
            $state.transitionTo 'content.tracks.list', {filter: $scope.query.toFilterString()}

        $scope.setFilter = (filter, value) ->
            $scope.query.setFilter filter, value
            $state.transitionTo 'content.tracks.list', {filter: $scope.query.toFilterString()}

        $scope.setListFilter = (filter, id) ->
            $scope.query.setListFilter filter, id
            $state.transitionTo 'content.tracks.list', {filter: $scope.query.toFilterString()}

        $scope.clearFilter = (filter) ->
            $scope.query.clearFilter filter
            $state.transitionTo 'content.tracks.list', {filter: $scope.query.toFilterString()}

        tracks.mainQuery.listen (searchResults) ->
            $scope.tracks = searchResults.tracks
            $scope.currentPage = parseInt(searchResults.current_page)
            $scope.totalPages = parseInt(searchResults.total_pages)
            delete $scope.nextPage
            delete $scope.prevPage

            $scope.nextPage = $scope.currentPage + 1 if $scope.currentPage < $scope.totalPages
            $scope.prevPage = $scope.currentPage - 1 if $scope.currentPage > 1
            $scope.pages = [1..$scope.totalPages]

        $scope.gotoPage = (page) ->
            $state.transitionTo 'content.tracks.list', {filter: $state.params.filter, page: page}

        $scope.$on '$destroy', -> tracks.mainQuery = tracks.createQuery()
]
