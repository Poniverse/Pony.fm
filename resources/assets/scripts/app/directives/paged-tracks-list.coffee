# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Logic
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

module.exports = angular.module('ponyfm').directive 'pfmPagedTracksList', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/paged-tracks-list.html'
    replace: true
    scope:
        type: '@'
        class: '@class'
        clickOverride: '&'
        hasOverride: '@'
        admin: '@'

    controller: [
        '$scope', 'tracks', '$state', '$location'
        ($scope, tracks, $state, $location) ->

            $scope.filters = tracks.filters
            $scope.query = tracks.mainQuery
            tracks.mainQuery.fromFilterString($state.params.filter)
            tracks.mainQuery.setPage $state.params.page || 1
            tracks.mainQuery.setAdmin $scope.admin || false

            $scope.toggleListFilter = (filter, id) ->
                $scope.query.toggleListFilter filter, id
                $state.transitionTo $state.current.name, {filter: $scope.query.toFilterString()}

            $scope.setFilter = (filter, value) ->
                $scope.query.setFilter filter, value
                $state.transitionTo $state.current.name, {filter: $scope.query.toFilterString()}

            $scope.setListFilter = (filter, id) ->
                $scope.query.setListFilter filter, id
                $state.transitionTo $state.current.name, {filter: $scope.query.toFilterString()}

            $scope.clearFilter = (filter) ->
                $scope.query.clearFilter filter
                $state.transitionTo $state.current.name, {filter: $scope.query.toFilterString()}

            $scope.newClickOverride = (id) ->
                $scope.clickOverride({id: id})

            typeEnum = switch
                when $scope.type == 'normal' then tracks.FetchType.NORMAL
                when $scope.type == 'all' then tracks.FetchType.ALL
                when $scope.type == 'unclassified' then tracks.FetchType.UNCLASSIFIED
                else tracks.FetchType.NORMAL

            tracks.mainQuery.fetch(typeEnum).done (searchResults) ->
                $scope.tracks = searchResults.tracks

                $scope.currentPage = parseInt(searchResults.current_page)
                $scope.totalPages = parseInt(searchResults.total_pages)
                delete $scope.nextPage
                delete $scope.prevPage

                $scope.nextPage = $scope.currentPage + 1 if $scope.currentPage < $scope.totalPages
                $scope.prevPage = $scope.currentPage - 1 if $scope.currentPage > 1
                $scope.allPages = [1..$scope.totalPages]

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
                #$scope.$emit 'pageChange', {filter: $state.params.filter, page: page}
                $state.go '.', {filter: $state.params.filter, page: page}

            $scope.showPageSelector = () ->
                $scope.pageSelectorShown = true
                focus('#pagination-jump-destination')

            $scope.hidePageSelector = () ->
                $scope.pageSelectorShown = false

            $scope.jumpToPage = (inputPageNumber) ->
                $scope.gotoPage(inputPageNumber)
    ]
