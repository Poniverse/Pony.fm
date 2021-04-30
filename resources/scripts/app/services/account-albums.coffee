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

module.exports = angular.module('ponyfm').factory('account-albums', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        def = null
        # the ID of the user whose albums are currently cached
        currentlyLoadedUserId = null

        albums = []

        self =
            getEdit: (id, force) ->
                url = '/api/web/albums/edit/' + id
                force = force || false
                return albums[id] if !force && albums[id]

                editDef = new $.Deferred()
                albums[id] = editDef
                $http.get(url).success (album) -> editDef.resolve album
                editDef.promise()

            refresh: (force = false, userId = window.pfm.auth.user.slug) ->
                return def if !force && def && userId == currentlyLoadedUserId

                def = new $.Deferred()
                $http.get("/api/web/users/#{userId}/albums").success (ownedAlbums) ->
                    currentlyLoadedUserId = userId
                    def.resolve(ownedAlbums)
                def.promise()

        self
])
