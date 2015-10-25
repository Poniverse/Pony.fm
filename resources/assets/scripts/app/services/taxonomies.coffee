angular.module('ponyfm').factory('taxonomies', [
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
