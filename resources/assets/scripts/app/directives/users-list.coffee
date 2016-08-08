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

module.exports = angular.module('ponyfm').directive 'pfmUsersList', () ->
    restrict: 'E'
    replace: true
    templateUrl: '/templates/directives/users-list.html'
    scope:
        users: '=users',
        class: '@class',
        size: '@size'

    controller: [
        '$scope', 'auth'
        ($scope, auth) ->
            if typeof $scope.size == 'undefined'
                $scope.size = 'large'

            if $scope.size == 'small'
                $scope.lgSize = '20'
                $scope.mdSize = '33'
                $scope.smSize = '50'
                $scope.xsSize = '100'
            else
                $scope.lgSize = '15'
                $scope.mdSize = ''
                $scope.smSize = '20'
                $scope.xsSize = '50'

            $scope.auth = auth.data
    ]
