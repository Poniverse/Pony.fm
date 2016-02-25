# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Peter Deltchev
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

module.exports = angular.module('ponyfm').directive 'pfmTrackEditor', () ->
    restrict: 'E'
    templateUrl: '/templates/directives/track-editor.html'
    scope:
        trackId: '=trackId'

    controller: [
        '$scope', '$dialog', 'auth', 'account-tracks', 'account-albums', 'taxonomies', 'images'
        ($scope, $dialog, auth, tracks, albums, taxonomies, images) ->
            $scope.isDirty = false
            $scope.isSaving = false
            $scope.taxonomies = taxonomies
            $scope.selectedSongsTitle = 'None'
            $scope.selectedSongs = {}
            $scope.albums = []
            $scope.selectedAlbum = null
            albumsDb = {}

            $scope.selectAlbum = (album) ->
                $scope.selectedAlbum = album
                $scope.track.album_id = if album then album.id else null
                $scope.isDirty = true

            $scope.setCover = (image, type) ->
                delete $scope.track.cover_id
                delete $scope.track.cover

                if image == null
                    $scope.track.remove_cover = true
                else if type == 'file'
                    $scope.track.cover = image
                else if type == 'gallery'
                    $scope.track.cover_id = image.id

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
                delete $scope.errors.lyrics if !$scope.track.is_vocal

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
                    $scope.$emit('track-updated', track)

                    $scope.track.is_published = true
                    $scope.isDirty = false
                    $scope.errors = {}
                    images.refresh true

                formData = new FormData();
                _.each $scope.track, (value, name) ->
                    if name == 'cover'
                        return if value == null
                        if typeof(value) == 'object'
                            formData.append name, value, value.name

                    if name == 'released_at' and value? and value != ''
                        formData.append name, value.toISOString()

                    else if value?
                        formData.append name, value

                if parseInt($scope.track.track_type_id) == 2
                    formData.append 'show_song_ids', _.map(_.values($scope.selectedSongs), (s) -> s.id).join()

                xhr.open 'POST', '/api/web/tracks/edit/' + $scope.track.id, true
                xhr.setRequestHeader 'X-XSRF-TOKEN', $.cookie('XSRF-TOKEN')
                $scope.isSaving = true
                xhr.send formData

            $scope.deleteTrack = () ->
                $dialog.messageBox('Delete ' + $scope.track.title, 'Are you sure you want to delete "' + $scope.track.title + '"?', [
                    {result: 'ok', label: 'Yes', cssClass: 'btn-danger'},
                    {result: 'cancel', label: 'No', cssClass: 'btn-primary'}
                ]).open().then (res) ->
                    return if res == 'cancel'
                    $.post('/api/web/tracks/delete/' + $scope.track.id)
                    .then -> $scope.$apply ->
                        $scope.$emit 'track-deleted'

            # ========================================
            #  The part where everything gets loaded!
            # ========================================
            tracks.getEdit($scope.trackId, true)
            .then (track)->
                $.when(
                    albums.refresh(false, track.user_id),
                    taxonomies.refresh()
                ).done (albums, taxonomies)->
                    # Update album data
                    $scope.albums.length = 0
                    albumsDb = {}
                    for album in albums
                        albumsDb[album.id] = album
                        $scope.albums.push album


                # Update track data

                # The release date is in UTC - make sure we treat it as such.
                if track.released_at
                    local_date = new Date(track.released_at)
                    utc_release_timestamp = local_date.getTime() + (local_date.getTimezoneOffset() * 60000);
                    utc_release_date = new Date(utc_release_timestamp)
                else utc_release_date = ''

                $scope.track =
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
                    released_at: utc_release_date
                    remove_cover: false
                    cover_id: track.cover_id
                    cover_url: track.cover_url
                    album_id: track.album_id
                    is_published: track.is_published
                    is_listed: track.is_listed

                $scope.selectedAlbum = if track.album_id then albumsDb[track.album_id] else null
                $scope.selectedSongs = {}
                $scope.selectedSongs[song.id] = song for song in track.show_songs
                updateSongDisplay()

            $scope.touchModel = -> $scope.isDirty = true

            $scope.$on '$locationChangeStart', (e) ->
                return if !$scope.isDirty
                e.preventDefault() if !confirm('Are you sure you want to leave this page without saving your changes?')
    ]
