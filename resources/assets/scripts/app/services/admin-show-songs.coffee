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

module.exports = angular.module('ponyfm').factory('admin-show-songs', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        def = null
        showsongs = []

        self =
            fetch: () ->
                url = '/api/web/admin/showsongs'
                def = new $.Deferred()
                $http.get(url).success (showsongs) ->
                    def.resolve(showsongs['showsongs'])
                def.promise()

            create: (name) ->
                url = '/api/web/admin/showsongs'
                def = new $.Deferred()
                $http.post(url, {title: name})
                .success (response) ->
                    def.resolve(response)
                .error (response) ->
                    def.reject(response)

                def.promise()

            rename: (song_id, new_name) ->
                url = "/api/web/admin/showsongs/#{song_id}"
                def = new $.Deferred()

                $http.put(url, {title: new_name})
                .success (response)->
                    def.resolve(response)

                .error (response)->
                    def.reject(response)

                def.promise()

            merge: (song_id_to_delete, destination_song_id) ->
                url = "/api/web/admin/showsongs/#{song_id_to_delete}"
                def = new $.Deferred()

                $http.delete(url, {params: {destination_song_id: destination_song_id}})
                .success (response)->
                    def.resolve(response)

                .error (response)->
                    def.reject(response)

                def.promise()
        self
])
