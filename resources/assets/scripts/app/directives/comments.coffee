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

module.exports = angular.module('ponyfm').directive 'pfmComments', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/comments.html'
    scope:
        resource: '=resource',
        type: '@type'

    controller: [
        '$scope', 'comments', 'auth'
        ($scope, comments, auth) ->

            $scope.isWorking = false
            $scope.content = ''
            $scope.auth = auth.data

            refresh = () ->
                comments.fetchList($scope.type, $scope.resource.id, true).done (comments) ->
                    $scope.resource.comments.length = 0
                    $scope.resource.comments.push comment for comment in comments.list
                    $scope.isWorking = false

            $scope.addComment = () ->
                content = $scope.content
                $scope.content = ''
                $scope.isWorking = true
                comments.addComment($scope.type, $scope.resource.id, content).done () ->
                    refresh()
    ]
