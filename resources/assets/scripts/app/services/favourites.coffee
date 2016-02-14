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

module.exports = angular.module('ponyfm').factory('favourites', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        tracksDef = null
        playlistsDef = null
        albumsDef = null

        self =
            toggle: (type, id) ->
                def = new $.Deferred()
                $http.post('/api/web/favourites/toggle', {type: type, id: id}).success (res) ->
                    def.resolve res

                def.promise()

            fetchTracks: (force) ->
                return tracksDef if !force && tracksDef
                tracksDef = new $.Deferred()
                $http.get('/api/web/favourites/tracks').success (res) ->
                    tracksDef.resolve res

                tracksDef

            fetchAlbums: (force) ->
                return albumsDef if !force && albumsDef
                albumsDef = new $.Deferred()
                $http.get('/api/web/favourites/albums').success (res) ->
                    albumsDef.resolve res

                albumsDef

            fetchPlaylists: (force) ->
                return playlistsDef if !force && playlistsDef
                playlistsDef = new $.Deferred()
                $http.get('/api/web/favourites/playlists').success (res) ->
                    playlistsDef.resolve res

                playlistsDef

        self
])
