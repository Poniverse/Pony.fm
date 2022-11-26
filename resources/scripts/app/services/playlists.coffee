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

module.exports = angular.module('ponyfm').factory('playlists', [
    '$rootScope', '$state', '$http', 'auth'
    ($rootScope, $state, $http, auth) ->
        playlistDef = null
        filterDef = null
        playlists = {}
        playlistPages = []

        class Query
            cacheDef: null
            page: 1
            listeners: []

            constructor: (@availableFilters) ->
                @filters = {}
                @hasLoadedFilters = false
                @resetFilters()

            resetFilters: ->
                _.each @availableFilters, (filter, name) =>
                    if filter.type == 'single'
                        @filters[name] = _.find filter.values, (f) -> f.isDefault
                    else
                        @filters[name] = {title: 'Any', selectedArray: [], selectedObject: {}}

            clearFilter: (type) ->
                @cachedDef = null
                @page = 1
                filter = @availableFilters[type]

                if filter.type == 'single'
                    @filters[type] = _.find filter.values, (f) -> f.isDefault
                else
                    currentFilter = @filters[type]
                    currentFilter.selectedArray = []
                    currentFilter.selectedObject = {}
                    currentFilter.title = 'Any'

            setPage: (page) ->
                @page = page
                @cachedDef = null

            setFilter: (type, value) ->
                @cachedDef = null
                @page = 1
                @filters[type] = value

            toFilterString: ->
                parts = []
                _.each @availableFilters, (filter, name) =>
                    filterName = filter.name
                    if filter.type == 'single'
                        return if @filters[name].query == ''
                        parts.push(filterName + '-' + @filters[name].query)
                    else
                        return if @filters[name].selectedArray.length == 0
                        parts.push(filterName + '-' + _.map(@filters[name].selectedArray, (f) -> f.id).join '-')

                return parts.join '!'

            fromFilterString: (str) ->
                @hasLoadedFilters = true
                @cachedDef = null
                @resetFilters()

                filters = (str || "").split '!'
                for queryFilter in filters
                    parts = queryFilter.split '-'
                    queryName = parts[0]

                    filterName = null
                    filter = null

                    for name,f of @availableFilters
                        continue if f.name != queryName
                        filterName = name
                        filter = f

                    return if !filter

                    if filter.type == 'single'
                        filterToSet = _.find filter.values, (f) -> f.query == parts[1]
                        filterToSet = (_.find filter.values, (f) -> f.isDefault) if filterToSet == null
                        @setFilter filterName, filterToSet
                    else
                        @toggleListFilter filterName, id for id in _.rest parts, 1

            listen: (listener) ->
                @listeners.push listener
                @cachedDef.done listener if @cachedDef

            fetch: () ->
                return @cachedDef if @cachedDef
                @cachedDef = new $.Deferred()
                playlistDef = @cachedDef

                query = '/api/web/playlists?'

                parts = ['page=' + @page]
                _.each @availableFilters, (filter, name) =>
                    if filter.type == 'single'
                        parts.push @filters[name].filter
                    else
                        queryName = filter.filterName
                        for item in @filters[name].selectedArray
                            parts.push queryName + "[]=" + item.id

                query += parts.join '&'
                $http.get(query).success (playlists) =>
                    @playlists = playlists
                    for listener in @listeners
                        listener playlists

                    playlistDef.resolve playlists

                playlistDef.promise()


        self =
            pinnedPlaylists: []
            filters: {}

            createQuery: -> new Query self.filters

            loadFilters: ->
                return filterDef if filterDef

                filterDef = new $.Deferred()
                self.filters.sort =
                    type: 'single'
                    name: 'sort'
                    values: [
                        {title: 'Most Favourited', query: 'favourites', isDefault: true, filter: 'order=favourite_count,desc'}
                        {title: 'Most Viewed', query: 'plays', isDefault: false, filter: 'order=view_count,desc'},
                        {title: 'Most Downloaded', query: 'downloads', isDefault: false, filter: 'order=download_count,desc'},
                        {title: 'Alphabetical', query: 'alphabetical', isDefault: false, filter: 'order=title,asc'},
                        {title: 'Latest', query: 'latest', isDefault: false, filter: 'order=created_at,desc'},
                        {title: 'Track count', query: 'tracks', isDefault: false, filter: 'order=track_count,desc'},
                    ]

                self.mainQuery = self.createQuery()
                filterDef.resolve self

                filterDef.promise()

            fetchList: (page, force) ->
                force = force || false
                page = 1 if !page
                return playlistPages[page] if !force && playlistPages[page]
                playlistDef = new $.Deferred()
                $http.get('/api/web/playlists?page=' + page).success (playlists) ->
                    playlistDef.resolve playlists
                    $rootScope.$broadcast 'playlists-feteched', playlists

                playlistPages[page] = playlistDef.promise()

            fetch: (id, force) ->
                force = force || false
                return playlists[id] if !force && playlists[id]
                def = new $.Deferred()
                $http.get('/api/web/playlists/' + id + '?log=true').success (playlist) ->
                    def.resolve playlist

                playlists[id] = def.promise()

            isPlaylistPinned: (id) ->
                _.find(self.pinnedPlaylists, (p) -> `p.id == id`) != undefined

            refreshOwned: (force = false, slug = window.pfm.auth.user?.slug) ->
                return playlistDef if !force && playlistDef

                playlistDef = new $.Deferred()

                if auth.data.isLogged
                    $http.get("/api/web/users/#{slug}/playlists").success (playlists) ->
                        playlistDef.resolve playlists
                else
                    playlistDef.resolve []

                playlistDef

            addTrackToPlaylist: (playlistId, trackId) ->
                def = new $.Deferred()
                $http.post('/api/web/playlists/' + playlistId + '/add-track', {track_id: trackId}).success (res) ->
                    def.resolve(res)

                def

            removeTrackFromPlaylist: (playlistId, trackId) ->
                def = new $.Deferred()
                $http.post('/api/web/playlists/' + playlistId + '/remove-track', {track_id: trackId}).success (res) ->
                    def.resolve(res)

                def

            refresh: () ->
                if auth.data.isLogged
                    $.getJSON('/api/web/playlists/pinned')
                        .done (playlists) -> $rootScope.$apply ->
                            self.pinnedPlaylists.length = 0
                            self.pinnedPlaylists.push playlist for playlist in playlists

            deletePlaylist: (playlist) ->
                def = new $.Deferred()
                $.post('/api/web/playlists/delete/' + playlist.id)
                    .then -> $rootScope.$apply ->
                        if _.some(self.pinnedPlaylists, (p) -> p.id == playlist.id)
                            currentIndex = _.indexOf(self.pinnedPlaylists, (t) -> t.id == playlist.id)
                            self.pinnedPlaylists.splice currentIndex, 1

                        if $state.is('playlist') && $state.params.id == playlist.id
                            $state.transitionTo 'home'

                        def.resolve()

                def

            editPlaylist: (playlist) ->
                def = new $.Deferred()
                $.post('/api/web/playlists/edit/' + playlist.id, playlist)
                    .done (res) ->
                        $rootScope.$apply ->
                            currentIndex = _.indexOf(self.pinnedPlaylists, (t) -> t.id == playlist.id)
                            isPinned = _.some(self.pinnedPlaylists, (p) -> p.id == playlist.id)

                            if res.is_pinned && !isPinned
                                self.pinnedPlaylists.push res
                                self.pinnedPlaylists.sort (left, right) -> left.title.localeCompare right.title
                                currentIndex = _.indexOf(self.pinnedPlaylists, (t) -> t.id == playlist.id)
                            else if !res.is_pinned && isPinned
                                self.pinnedPlaylists.splice currentIndex, 1
                                currentIndex = _.indexOf(self.pinnedPlaylists, (t) -> t.id == playlist.id)

                            if res.is_pinned
                                current = self.pinnedPlaylists[currentIndex]
                                _.forEach res, (value, name) -> current[name] = value
                                self.pinnedPlaylists.sort (left, right) -> left.title.localeCompare right.title

                            def.resolve res
                            $rootScope.$broadcast 'playlist-updated', res

                    .fail (res)->
                        $rootScope.$apply ->
                            errors = {}
                            _.each res.responseJSON.errors, (value, key) -> errors[key] = value.join ', '
                            def.reject errors

                def

            createPlaylist: (playlist) ->
                def = new $.Deferred()
                $.post('/api/web/playlists/create', playlist)
                    .done (res) ->
                        $rootScope.$apply ->
                            if res.is_pinned
                                self.pinnedPlaylists.push res
                                self.pinnedPlaylists.sort (left, right) -> left.title.localeCompare right.title

                            def.resolve res

                    .fail (res)->
                        $rootScope.$apply ->
                            errors = {}
                            _.each res.responseJSON.errors, (value, key) -> errors[key] = value.join ', '
                            def.reject errors

                def


        self.refresh()
        self
])

