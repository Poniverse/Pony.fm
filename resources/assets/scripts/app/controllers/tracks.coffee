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

module.exports = angular.module('ponyfm').controller "tracks", [
    '$scope', 'tracks', '$state', 'focus'
    ($scope, tracks, $state, focus) ->
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
            $scope.allPages = [1..$scope.totalPages]

            # TODO: turn this into a directive
            # The actual first page will always be in the paginator.
            $scope.pages = [1]

            # This logic determines how many pages to add prior to the current page, if any.
            firstPage = Math.max(2, $scope.currentPage-3)
            $scope.pages = $scope.pages.concat [firstPage..$scope.currentPage] unless $scope.currentPage == 1

            pagesLeftToAdd = 8-$scope.pages.length

            lastPage = Math.min($scope.totalPages - 1, $scope.currentPage+1+pagesLeftToAdd)
            $scope.pages = $scope.pages.concat([$scope.currentPage+1..lastPage]) unless $scope.currentPage >= lastPage

            # The actual last page will always be in the paginator.
            $scope.pages.push($scope.totalPages) unless $scope.totalPages in $scope.pages

        $scope.pageSelectorShown = false

        $scope.gotoPage = (page) ->
            console.log(page)
            $state.transitionTo 'content.tracks.list', {filter: $state.params.filter, page: page}

        $scope.showPageSelector = () ->
            $scope.pageSelectorShown = true
            focus('#pagination-jump-destination')

        $scope.hidePageSelector = () ->
            $scope.pageSelectorShown = false


        $scope.jumpToPage = (inputPageNumber) ->
            $scope.gotoPage(inputPageNumber)

        $scope.$on '$destroy', -> tracks.mainQuery = tracks.createQuery()
]
