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

module.exports = angular.module('ponyfm').factory('notifications', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        self =
            notificationList: []

            getNotifications: () ->
                def = new $.Deferred()

                $http.get('/api/web/notifications').success (response) ->
                    self.notificationList = response.notifications
                    $rootScope.$broadcast 'notificationsUpdated'
                    def.resolve response.notifications

                def.promise()

            getNotificationCount: () ->
                return self.notificationList.length

            markAllAsRead: () ->
                unread = []

                for n, notifObject of self.notificationList
                    if !notifObject.is_read
                        unread.push notifObject.id.toString()

                $http.put('/api/web/notifications/mark-as-read', {notification_ids: unread}).success (response) ->
                    console.log response

                console.log unread

        self
])
