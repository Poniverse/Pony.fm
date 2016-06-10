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
        '$scope', 'notifications', '$timeout', '$rootScope', '$http'
        ($scope, notifications, $timeout, $rootScope, $http) ->
            $scope.notifications = []
            isTimeoutScheduled = false

            # TODO: ADD REFRESH BUTTON

            $rootScope.$on 'shouldUpdateNotifications', () ->
                refreshNotifications()

            checkSubscription = () ->
                navigator.serviceWorker.ready.then((reg) ->
                    reg.pushManager.subscribe({userVisibleOnly: true}).then((sub) ->
                        console.log 'Push sub', JSON.stringify(sub)
                        subData = JSON.stringify(sub)
                        $http.post('/api/web/notifications/subscribe', {subscription: subData})
                    )
                )

            refreshNotifications = () ->
                notifications.getNotifications().done (result) ->
                    if $scope.notifications.length > 0
                        if result[0].id != $scope.notifications[0].id || result[0].is_read != $scope.notifications[0].is_read
                            $scope.notifications = result
                    else if result.length > 0
                        $scope.notifications = result
                        
                    $scope.nCount = $scope.notifications.length

                    if !isTimeoutScheduled
                        scheduleTimeout()

            scheduleTimeout = () ->
                isTimeoutScheduled = true
                $timeout(() ->
                    refreshNotifications()
                    isTimeoutScheduled = false
                , 60000)

            checkSubscription()
            refreshNotifications()
    ]
