angular.module('ponyfm').factory('favourites', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        tracksDef = null
        playlistsDef = null
        albumsDef = null

        self =
            toggle: (type, id) ->
                def = new $.Deferred()
                $http.post('/api/web/favourites/toggle', {type: type, id: id, _token: pfm.token}).success (res) ->
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
