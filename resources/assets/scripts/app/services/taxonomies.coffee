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

module.exports = angular.module('ponyfm').factory('taxonomies', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        def = null

        self =
            trackTypes: []
            trackTypesWithTracks: []
            licenses: []
            genres: []
            genresWithTracks: []
            showSongs: []
            showSongsWithTracks: []

            refresh: () ->
                return def.promise() if def != null

                def = new $.Deferred()
                $http.get('/api/web/taxonomies/all')
                    .success (taxonomies) ->
                        for t in taxonomies.track_types
                            self.trackTypes.push t
                            self.trackTypesWithTracks.push t if t.track_count > 0

                        for t in taxonomies.genres
                            self.genres.push t
                            self.genresWithTracks.push t if t.track_count > 0

                        for t in taxonomies.show_songs
                            self.showSongs.push t
                            self.showSongsWithTracks.push t if t.track_count > 0

                        self.licenses.push t for t in taxonomies.licenses
                        def.resolve self

                def.promise()

        self
])
