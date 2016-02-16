# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Peter Deltchev
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

module.exports = angular.module('ponyfm').factory('albums', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        albumPages = []
        albums = {}

        self =
            filters: {}

            fetchList: (page, force) ->
                force = force || false
                page = 1 if !page
                return albumPages[page] if !force && albumPages[page]
                albumsDef = new $.Deferred()
                $http.get('/api/web/albums?page=' + page).success (albums) ->
                    albumsDef.resolve albums
                    $rootScope.$broadcast 'albums-feteched', albums

                albumPages[page] = albumsDef.promise()

            fetch: (id, force) ->
                force = force || false
                id = 1 if !id
                return albums[id] if !force && albums[id]
                albumsDef = new $.Deferred()
                $http.get('/api/web/albums/' + id + '?log=true').success (albums) ->
                    albumsDef.resolve albums

                albums[id] = albumsDef.promise()

        self
])
