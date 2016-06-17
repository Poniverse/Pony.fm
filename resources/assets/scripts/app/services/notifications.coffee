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
            serviceWorkerSupported: true

            getNotifications: () ->
                def = new $.Deferred()

                $http.get('/api/web/notifications').success (response) ->
                    self.notificationList = response.notifications
                    $rootScope.$broadcast 'notificationsUpdated'
                    def.resolve response.notifications

                def.promise()

            getNotificationCount: () ->
                return self.notificationList.length
            
            getUnreadCount: () ->
                count = 0
                
                for n, notifObject of self.notificationList
                    if !notifObject.is_read
                        count++
                
                return count

            markAllAsRead: () ->
                def = new $.Deferred()
                unread = []

                for n, notifObject of self.notificationList
                    if !notifObject.is_read
                        unread.push notifObject.id

                if unread.length > 0
                    $http.put('/api/web/notifications/mark-as-read', {notification_ids: unread}).success (response) ->
                        if response.notifications_updated != 0
                            $rootScope.$broadcast 'shouldUpdateNotifications'

                        def.resolve response.notifications_updated

                    def.promise()
                else
                    return 0

            subscribe: () ->
                def = new $.Deferred()
                navigator.serviceWorker.ready.then (reg) ->
                    reg.pushManager.subscribe({userVisibleOnly: true}).then (sub) ->
                        console.log 'Push sub', JSON.stringify(sub)
                        self.sendSubscriptionToServer(sub).done (result) ->
                            def.resolve result

                def.promise()

            unsubscribe: () ->
                def = new $.Deferred()
                navigator.serviceWorker.ready.then (reg) ->
                    reg.pushManager.getSubscription().then (sub) ->
                        sub.unsubscribe().then (result) ->
                            self.removeSubscriptionFromServer(sub).done (result) ->
                                def.resolve true
                        .catch (e) ->
                            console.warn('Unsubscription error: ', e)
                            def.resolve false

                def.promise()

            sendSubscriptionToServer: (sub) ->
                def = new $.Deferred()
                subData = JSON.stringify(sub)
                $http.post('/api/web/notifications/subscribe', {subscription: subData}).success () ->
                    def.resolve true
                .error () ->
                    def.resolve false

                def.promise()

            removeSubscriptionFromServer: (sub) ->
                def = new $.Deferred()
                subData = JSON.stringify(sub)
                $http.post('/api/web/notifications/unsubscribe', {subscription: subData}).success () ->
                    def.resolve true
                .error () ->
                    def.resolve false

                def.promise()

            checkSubscription: () ->
                def = new $.Deferred()

                if 'serviceWorker' of navigator
                    if !self.checkPushSupport()
                        def.resolve -1

                    navigator.serviceWorker.ready.then (reg) ->
                        reg.pushManager.getSubscription().then (sub) ->
                            if !sub
                                def.resolve 0

                            self.sendSubscriptionToServer(sub)

                            def.resolve 1


                else
                    console.warn('Service worker isn\'t supported.')
                    def.resolve -1

                def.promise()

            checkPushSupport: () ->
                if !('showNotification' of ServiceWorkerRegistration.prototype)
                    console.warn('Notifications aren\'t supported.')
                    return false

                if Notification.permission == 'denied'
                    console.warn('The user has blocked notifications.')
                    return false

                if !('PushManager' of window)
                    console.warn('Push messaging isn\'t supported.')
                    return false

                # If Chrome 50+
                if !!window.chrome && !!window.chrome.webstore
                    if parseInt(navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./)[2]) >= 50
                        return true
                # If Firefox 46+
                else if typeof InstallTrigger != 'undefined'
                    if parseInt(navigator.userAgent.match(/Firefox\/([0-9]+)\./)[1]) >= 46
                        return true

                return false

        self
])
