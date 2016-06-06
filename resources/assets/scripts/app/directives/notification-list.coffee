# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Josef Citrine
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

module.exports = angular.module('ponyfm').directive 'pfmNotificationList', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/notification-list.html'
    replace: true
    scope: {}

    controller: [
        '$scope', 'notifications', '$timeout'
        ($scope, notifications, $timeout) ->
            $scope.notifications = []
            isTimeoutScheduled = false

            # TODO: ADD REFRESH BUTTON

            refreshNotifications = () ->
                notifications.getNotifications().done (result) ->
                    if $scope.notifications.length > 0
                        if result[0].text != $scope.notifications[0].text
                            $scope.notifications = result
                    else if result.length > 0
                        $scope.notifications = result
                        
                    $scope.nCount = $scope.notifications.length
                    
                    scheduleTimeout()

            scheduleTimeout = () ->
                isTimeoutScheduled = true
                $timeout(() ->
                    refreshNotifications()
                    isTimeoutScheduled = false
                , 60000)

            

            refreshNotifications()
    ]
