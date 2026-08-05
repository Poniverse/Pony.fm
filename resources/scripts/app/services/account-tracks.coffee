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

module.exports = angular.module('ponyfm').factory('account-tracks', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        cache = {}

        self =
            clearCache: () -> cache = {}

            getEdit: (id, force) ->
                url = '/api/web/tracks/edit/' + id
                force = force || false
                return cache[url] if !force && cache[url]

                def = new $.Deferred()
                cache[url] = def
                $http.get(url).success (track) -> def.resolve track
                def.promise()

            refresh: (query = 'created_at,desc', force = false, userId = window.pfm.auth.user.slug) ->
                url = "/api/web/users/#{userId}/tracks?" + query
                return cache[url] if !force && cache[url]

                def = new $.Deferred()
                cache[url] = def
                $http.get(url).success (tracks) -> def.resolve tracks
                def.promise()

        self
])
