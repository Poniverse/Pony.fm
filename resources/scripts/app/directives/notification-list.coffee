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

module.exports = angular.module('ponyfm').directive 'pfmNotificationList', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/notification-list.html'
    replace: true
    scope: {}

    controller: [
        '$scope', 'notifications', '$timeout', '$rootScope', '$http'
        ($scope, notifications, $timeout, $rootScope, $http) ->
            $scope.notifications = []
            $scope.subscribed = false
            $scope.switchDisabled = true
            $scope.switchHidden = true
            isTimeoutScheduled = false

            # TODO: ADD REFRESH BUTTON

            $rootScope.$on 'shouldUpdateNotifications', () ->
                refreshNotifications()
            
            $scope.switchToggled = () ->
                if $scope.subscribed
                    $scope.switchDisabled = true
                    notifications.subscribe().done (result) ->
                        if result
                            $scope.switchDisabled = false
                else
                    $scope.switchDisabled = true
                    notifications.unsubscribe().done (result) ->
                        if result
                            $scope.switchDisabled = false


            checkSubscription = () ->
                if 'serviceWorker' of navigator && notifications.serviceWorkerSupported
                    $scope.disabled = true
                    notifications.checkSubscription().done (subStatus) ->
                        switch subStatus
                            when 0
                                $scope.subscribed = false
                                $scope.switchDisabled = false
                            when 1
                                $scope.subscribed = true
                                $scope.switchDisabled = false
                            else
                                $scope.subscribed = false
                                $scope.switchDisabled = true
                                $scope.hidden = true
                else
                    $scope.switchHidden = true

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

            #checkSubscription()
            refreshNotifications()
    ]
