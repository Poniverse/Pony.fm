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

module.exports = angular.module('ponyfm').factory('admin-genres', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        def = null
        genres = []

        self =
            fetch: () ->
                url = '/api/web/admin/genres'
                def = new $.Deferred()
                $http.get(url).success (genres) ->
                    def.resolve(genres['genres'])
                def.promise()

            create: (name) ->
                url = '/api/web/admin/genres'
                def = new $.Deferred()
                $http.post(url, {name: name})
                    .success (response) ->
                        def.resolve(response)
                    .error (response) ->
                        def.reject(response)

                def.promise()

            rename: (genre_id, new_name) ->
                url = "/api/web/admin/genres/#{genre_id}"
                def = new $.Deferred()

                $http.put(url, {name: new_name})
                    .success (response)->
                        def.resolve(response)

                    .error (response)->
                        def.reject(response)

                def.promise()

            merge: (genre_id_to_delete, destination_genre_id) ->
                url = "/api/web/admin/genres/#{genre_id_to_delete}"
                def = new $.Deferred()

                $http.delete(url, {params: {destination_genre_id: destination_genre_id}})
                    .success (response)->
                        def.resolve(response)

                    .error (response)->
                        def.reject(response)
                        
                def.promise()
        self
])
