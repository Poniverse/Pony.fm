window.pfm.preloaders['account-track'] = [
    'account-tracks', 'account-albums', 'taxonomies', '$state'
    (tracks, albums, taxonomies, state) ->
        $.when.all [albums.refresh(), taxonomies.refresh(), tracks.getEdit(state.params.track_id, true)]
]

angular.module('ponyfm').controller "account-track", [
    '$scope', '$state', 'taxonomies', '$dialog', 'account-albums', 'account-tracks', 'images'
    ($scope, $state, taxonomies, $dialog, albums, tracks, images) ->
        $scope.isDirty = false
        $scope.isSaving = false
        $scope.taxonomies = taxonomies
        $scope.selectedSongsTitle = 'None'
        $scope.selectedSongs = {}
        $scope.albums = []
        $scope.selectedAlbum = null

        albumsDb = {}
        albums.refresh().done (albums) ->
            $scope.albums.legnth = 0
            albumsDb = {}
            for album in albums
                albumsDb[album.id] = album
                $scope.albums.push album

        $scope.selectAlbum = (album) ->
            $scope.selectedAlbum = album
            $scope.edit.album_id = if album then album.id else null
            $scope.isDirty = true

        $scope.setCover = (image, type) ->
            delete $scope.edit.cover_id
            delete $scope.edit.cover

            if image == null
                $scope.edit.remove_cover = true
            else if type == 'file'
                $scope.edit.cover = image
            else if type == 'gallery'
                $scope.edit.cover_id = image.id

            $scope.isDirty = true

        updateSongDisplay = () ->
            if _.size $scope.selectedSongs
                $scope.selectedSongsTitle = (_.map _.values($scope.selectedSongs), (s) -> s.title).join(', ')
            else
                $scope.selectedSongsTitle = 'None'

        $scope.toggleSong = (song) ->
            $scope.isDirty = true
            if $scope.selectedSongs[song.id]
                delete $scope.selectedSongs[song.id]
            else
                $scope.selectedSongs[song.id] = song

            updateSongDisplay()

        $scope.updateIsVocal = () ->
            delete $scope.errors.lyrics if !$scope.edit.is_vocal

        $scope.updateTrack = () ->
            xhr = new XMLHttpRequest()
            xhr.onload = -> $scope.$apply ->
                $scope.isSaving = false
                if xhr.status != 200
                    errors =
                        if xhr.getResponseHeader('content-type') == 'application/json'
                            $.parseJSON(xhr.responseText).errors
                        else
                            ['There was an unknown error!']

                    $scope.errors = {}
                    _.each errors, (value, key) -> $scope.errors[key] = value.join ', '
                    return

                track = $.parseJSON(xhr.responseText)

                trackDbItem = $scope.data.selectedTrack
                trackDbItem.title = $scope.edit.title
                trackDbItem.is_explicit = $scope.edit.is_explicit
                trackDbItem.is_vocal = $scope.edit.is_vocal
                trackDbItem.genre_id = $scope.edit.genre_id
                trackDbItem.is_published = true
                trackDbItem.cover_url = track.real_cover_url
                $scope.isDirty = false
                $scope.errors = {}
                images.refresh true

            formData = new FormData();
            _.each $scope.edit, (value, name) ->
                if name == 'cover'
                    return if value == null
                    if typeof(value) == 'object'
                        formData.append name, value, value.name
                else if value != null
                    formData.append name, value

            if parseInt($scope.edit.track_type_id) == 2
                formData.append 'show_song_ids', _.map(_.values($scope.selectedSongs), (s) -> s.id).join()

            xhr.open 'POST', '/api/web/tracks/edit/' + $scope.edit.id, true
            xhr.setRequestHeader 'X-CSRF-Token', pfm.token
            $scope.isSaving = true
            xhr.send formData

        tracks.getEdit($state.params.track_id).done (track) ->
            $scope.edit =
                id: track.id
                title: track.title
                description: track.description
                lyrics: track.lyrics
                is_explicit: track.is_explicit
                is_downloadable: track.is_downloadable
                is_vocal: track.is_vocal
                license_id: track.license_id
                genre_id: track.genre_id
                track_type_id: track.track_type_id
                released_at: if track.released_at then track.released_at.date else ''
                remove_cover: false
                cover: track.cover_url
                album_id: track.album_id
                is_published: track.is_published
                is_listed: track.is_listed

            $scope.selectedAlbum = if track.album_id then albumsDb[track.album_id] else null
            $scope.selectedSongs = {}
            $scope.selectedSongs[song.id] = song for song in track.show_songs
            updateSongDisplay()

        $scope.touchModel = -> $scope.isDirty = true

        $scope.deleteTrack = (track) ->
            $dialog.messageBox('Delete ' + track.title, 'Are you sure you want to delete "' + track.title + '"? This cannot be undone.', [
                {result: 'ok', label: 'Yes', cssClass: 'btn-danger'}, {result: 'cancel', label: 'No', cssClass: 'btn-primary'}
            ]).open().then (res) ->
                return if res == 'cancel'
                $.post('/api/web/tracks/delete/' + track.id, {_token: window.pfm.token})
                    .then -> $scope.$apply ->
                        $scope.$emit 'track-deleted'
                        $state.transitionTo 'account.tracks'

        $scope.$on '$locationChangeStart', (e) ->
            return if !$scope.isDirty
            e.preventDefault() if !confirm('Are you sure you want to leave this page without saving your changes?')
]
