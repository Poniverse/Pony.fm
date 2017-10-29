# Pony.fm - A community for pony fan music.
# Copyright (C) 2015-2017 Peter Deltchev
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

module.exports = angular.module('ponyfm').factory('tracks', [
    '$rootScope', '$http', 'taxonomies'
    ($rootScope, $http, taxonomies) ->
        filterDef = null
        trackCache = {}

        class Query
            cachedDef: null
            page: 1
            admin: false
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

            isIdSelected: (type, id) ->
                @filters[type].selectedObject[id] != undefined

            listen: (listener) ->
                @listeners.push listener
                @cachedDef.done listener if @cachedDef

            setListFilter: (type, id) ->
                @cachedDef = null
                @page = 1
                filterToAdd = _.find @availableFilters[type].values, (f) -> `f.id == id`
                return if !filterToAdd

                filter = @filters[type]
                filter.selectedArray = [filterToAdd]
                filter.selectedObject = {}
                filter.selectedObject[id] = filterToAdd
                filter.title = filterToAdd.title

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

            toggleListFilter: (type, id) ->
                @cachedDef = null
                @page = 1
                filter = @filters[type]

                if filter.selectedObject[id]
                    delete filter.selectedObject[id]
                    filter.selectedArray.splice _.indexOf(filter.selectedArray, (f) -> f.id == id), 1
                else
                    filterToAdd = _.find @availableFilters[type].values, (f) -> `f.id == id`
                    return if !filterToAdd
                    filter.selectedObject[id] = filterToAdd
                    filter.selectedArray.push filterToAdd

                if filter.selectedArray.length == 0
                    filter.title = 'Any'
                else if filter.selectedArray.length == 1
                    filter.title = filter.selectedArray[0].title
                else
                    filter.title = filter.selectedArray.length + ' selected'

            setPage: (page) ->
                @page = page
                @cachedDef = null

            setAdmin: (value) ->
                @cachedDef = null
                @admin = value

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

            fetch: (type) ->
                return @cachedDef if @cachedDef
                @cachedDef = new $.Deferred()
                trackDef = @cachedDef

                query = '/api/web/tracks?'

                if type == self.FetchType.ALL && @admin
                    query = '/api/web/admin/tracks?'

                if type == self.FetchType.UNCLASSIFIED && @admin
                    query = '/api/web/admin/tracks/unclassified?'


                parts = ['page=' + @page]
                _.each @availableFilters, (filter, name) =>
                    if filter.type == 'single'
                        parts.push @filters[name].filter
                    else
                        queryName = filter.filterName
                        for item in @filters[name].selectedArray
                            parts.push queryName + "[]=" + item.id

                query += parts.join '&'
                $http.get(query).success (tracks) =>
                    @tracks = tracks
                    for listener in @listeners
                        listener tracks


                    trackDef.resolve tracks

                trackDef.promise()

        self =
            filters: {}

            FetchType:
                NORMAL: 0
                ALL: 1
                UNCLASSIFIED: 2

            fetch: (id, force) ->
                force = force || false
                return trackCache[id] if !force && trackCache[id]
                trackDef = new $.Deferred()
                $http.get('/api/web/tracks/' + id + '?log=true').success (track) ->
                    trackDef.resolve track

                trackCache[id] = trackDef.promise()

            createQuery: -> new Query self.filters

            loadFilters: ->
                return filterDef if filterDef

                filterDef = new $.Deferred()
                self.filters.isVocal =
                    type: 'single'
                    name: 'vocal'
                    values: [
                        {title: 'Either', query: '', isDefault: true, filter: ''},
                        {title: 'Yes', query: 'yes', isDefault: false, filter: 'is_vocal=true'},
                        {title: 'No', query: 'no', isDefault: false, filter: 'is_vocal=false'}
                    ]

                self.filters.sort =
                    type: 'single'
                    name: 'sort'
                    values: [
                        {title: 'Latest', query: '', isDefault: true, filter: 'order=published_at,desc'},
                        {title: 'Most Played', query: 'plays', isDefault: false, filter: 'order=play_count,desc'},
                        {title: 'Most Downloaded', query: 'downloads', isDefault: false, filter: 'order=download_count,desc'},
                        {title: 'Most Favourited', query: 'favourites', isDefault: false, filter: 'order=favourite_count,desc'}
                        {title: 'Alphabetical', query: 'alphabetical', isDefault: false, filter: 'order=title,asc'},
                    ]

                self.filters.genres =
                    type: 'list'
                    name: 'genres'
                    values: []
                    filterName: 'genres'

                self.filters.trackTypes =
                    type: 'list'
                    name: 'types'
                    values: []
                    filterName: 'types'

                self.filters.showSongs =
                    type: 'list'
                    name: 'songs'
                    values: []
                    filterName: 'songs'
                
                self.filters.archive =
                    type: 'single'
                    name: 'archive'
                    values: [
                        {title: 'None', query: '', isDefault: true, filter: ''}
                        {title: 'Equestrian Beats', query: 'eqbeats', isDefault: false, filter: 'archive=eqbeats'}
                        {title: 'MLP Music Archive', query: 'mlpma', isDefault: false, filter: 'archive=mlpma'}
                        {title: 'Ponify', query: 'ponify', isDefault: false, filter: 'archive=ponify'}
                    ]

                taxonomies.refresh().done (taxes) ->
                    for genre in taxes.genresWithTracks
                        self.filters.genres.values.push
                            title: genre.name
                            id: genre.id

                    for type in taxes.trackTypesWithTracks
                        self.filters.trackTypes.values.push
                            title: type.title
                            id: type.id

                    for song in taxes.showSongsWithTracks
                        self.filters.showSongs.values.push
                            title: song.title
                            id: song.id

                    self.mainQuery = self.createQuery()
                    filterDef.resolve self

                filterDef.promise()

        self
])
