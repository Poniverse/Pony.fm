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

module.exports = angular.module('ponyfm').factory('images', [
    '$rootScope'
    ($rootScope) ->
        def = null
        currentlyLoadedUserId = null

        self =
            images: []
            isLoading: true

            refresh: (force, userId = window.pfm.auth.user.id) ->
                return def if !force && def && userId == currentlyLoadedUserId
                def = new $.Deferred()

                self.images = []
                self.isLoading = true

                $.getJSON("/api/web/users/#{userId}/images").done (images) -> $rootScope.$apply ->
                    currentlyLoadedUserId = userId
                    self.images = images
                    self.isLoading = false
                    def.resolve images

                return def

        return self
])
