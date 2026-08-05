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

module.exports = angular.module('ponyfm').directive 'pfmFavouriteButton', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/favourite-button.html'
    scope:
        resource: '=resource',
        class: '@class',
        type: '@type'
    replace: true

    controller: [
        '$scope', 'favourites', 'auth'
        ($scope, favourites, auth) ->
            $scope.auth = auth.data

            $scope.isWorking = false
            $scope.toggleFavourite = () ->
                $scope.isWorking = true
                favourites.toggle($scope.type, $scope.resource.id).done (res) ->
                    $scope.isWorking = false
                    $scope.resource.user_data.is_favourited = res.is_favourited
    ]
