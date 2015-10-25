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

angular.module('ponyfm').factory('artists', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        artistPage = []
        artists = {}
        artistContent = {}
        artistFavourites = {}

        self =
            filters: {}

            fetchList: (page, force) ->
                force = force || false
                page = 1 if !page
                return artistPage[page] if !force && artistPage[page]
                artistsDef = new $.Deferred()
                $http.get('/api/web/artists?page=' + page).success (albums) ->
                    artistsDef.resolve albums
                    $rootScope.$broadcast 'artists-feteched', albums

                artistPage[page] = artistsDef.promise()

            fetch: (slug, force) ->
                force = force || false
                slug = 1 if !slug
                return artists[slug] if !force && artists[slug]
                artistsDef = new $.Deferred()
                $http.get('/api/web/artists/' + slug)
                    .success (albums) ->
                        artistsDef.resolve albums
                    .catch () ->
                        artistsDef.reject()

                artists[slug] = artistsDef.promise()

            fetchContent: (slug, force) ->
                force = force || false
                slug = 1 if !slug
                return artistContent[slug] if !force && artistContent[slug]
                artistsDef = new $.Deferred()
                $http.get('/api/web/artists/' + slug + '/content')
                    .success (albums) ->
                        artistsDef.resolve albums
                    .catch () ->
                        artistsDef.reject()

                artistContent[slug] = artistsDef.promise()

            fetchFavourites: (slug, force) ->
                force = force || false
                slug = 1 if !slug
                return artistFavourites[slug] if !force && artistFavourites[slug]
                artistsDef = new $.Deferred()
                $http.get('/api/web/artists/' + slug + '/favourites').success (albums) ->
                    artistsDef.resolve albums

                artistFavourites[slug] = artistsDef.promise()

        self
])
