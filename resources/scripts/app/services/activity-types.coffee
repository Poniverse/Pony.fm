# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Feld0
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

module.exports = angular.module('ponyfm').factory('activity-types', [
    '$location',
    ($location) ->
        self =
            getUnsubscribeMessage: () ->
                if $location.search().unsubscribedMessageKey?
                    if $location.search().unsubscribedUser?
                        return self.generateUnsubscribeMessage(
                            $location.search().unsubscribedMessageKey,
                            $location.search().unsubscribedUser,
                        )
                    else
                        return self.generateUnsubscribeMessage($location.search().unsubscribedMessageKey)
                else
                    return null

            generateUnsubscribeMessage: (activityType, displayName = null) ->
                # TODO: get these messages from the backend
                switch parseInt(activityType)
                    when 1 then activityString = 'updates from the Pony.fm team'
                    when 2 then activityString = 'new tracks by users you follow'
                    when 3 then activityString = 'new albums by users you follow'
                    when 4 then activityString = 'new playlists by users you follow'
                    when 5 then activityString = 'when you get new followers'
                    when 6 then activityString = 'when someone leaves you a comment'
                    when 7 then activityString = 'when something of yours is favourited'
                    else throw "#{activityType} is an invalid activity type!"

                if displayName
                    return "#{displayName} - you've been unsubscribed from email notifications for #{activityString}. You can re-enable them by logging in and going to your account settings."
                else
                    return "You successfully unsubscribed from email notifications for #{activityString}. If you want, you can re-subscribe below."
        return self
])
